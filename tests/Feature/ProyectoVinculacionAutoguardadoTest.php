<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El autoguardado del FORM-DVUS-001 guarda cada sección por separado: un dato que
 * la base de datos rechaza en una sección no debe revertir lo capturado en las
 * demás, ni dejar que "Guardar borrador" o el envío reporten éxito.
 */
class ProyectoVinculacionAutoguardadoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_una_seccion_que_falla_no_revierte_los_textos_narrativos(): void
    {
        [$component, $proyecto] = $this->componenteConProyecto();
        $component->nombre_proyecto = str_repeat('N', 300);
        $component->objetivo_general = 'Fortalecer la planificación urbana de El Paraíso.';
        $component->definicion_problema = 'El municipio carece de un plan de ordenamiento vigente.';

        $guardado = $component->autoGuardarBorrador();

        $this->assertFalse($guardado);
        $this->assertSame('error', $component->estadoAutoGuardado);
        $this->assertSame(['nombre'], array_keys($component->seccionesNoGuardadas));
        $this->assertStringContainsString('nombre del proyecto', $component->resumenSeccionesNoGuardadas());

        $proyecto->refresh();
        $this->assertSame('Fortalecer la planificación urbana de El Paraíso.', $proyecto->objetivo_general);
        $this->assertSame('El municipio carece de un plan de ordenamiento vigente.', $proyecto->definicion_problema);
    }

    public function test_compromisos_largos_y_tipo_de_entidad_fuera_del_formato_se_guardan(): void
    {
        [$component, $proyecto] = $this->componenteConProyecto();
        $catalogo = EntidadContraparte::create([
            'nombre' => 'Alcaldía de prueba '.uniqid(),
            'tipo_entidad' => 'Gobierno local',
        ]);
        $compromisos = str_repeat('Compromiso de la contraparte. ', 20);
        $component->entidad_contraparte = [[
            'entidad_contraparte_id' => $catalogo->id,
            'nombre' => $catalogo->nombre,
            'tipo_entidad' => $catalogo->tipo_entidad,
            'descripcion_acuerdos' => $compromisos,
            'instrumento_formalizacion' => [[
                'tipo_documento' => 'carta_intenciones',
                'documento_url' => 'instrumentos/carta.pdf',
                'nombre_archivo' => 'carta.pdf',
                'documento_file' => null,
            ]],
        ]];

        $this->assertTrue($component->autoGuardarBorrador());

        $pivot = $proyecto->entidad_contraparte_proyecto()->firstOrFail();
        $this->assertGreaterThan(255, mb_strlen($compromisos));
        $this->assertSame($compromisos, $pivot->descripcion_acuerdos);
        $this->assertNull($pivot->tipo_entidad);
    }

    public function test_guardar_borrador_no_reporta_exito_si_una_seccion_fallo(): void
    {
        [$component] = $this->componenteConProyecto();
        $component->nombre_proyecto = str_repeat('N', 300);

        $component->borrador();

        $notificacion = collect(session('flash_notifications'))->last();
        $this->assertSame('No se pudo guardar el borrador', $notificacion['title']);
        $this->assertSame('danger', $notificacion['type']);
        $this->assertFalse(collect(session('flash_notifications'))->contains('title', 'Borrador guardado'));
    }

    public function test_enviar_se_detiene_si_el_autoguardado_falla(): void
    {
        [$component] = $this->componenteConProyecto();
        $component->nombre_proyecto = str_repeat('N', 300);

        $component->abrirModalEnviar();

        $this->assertFalse($component->showEnviarModal);
        $this->assertSame('No se pudo enviar el proyecto', collect(session('flash_notifications'))->last()['title']);
    }

    public function test_campos_obligatorios_faltantes_revisa_lo_guardado(): void
    {
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto incompleto '.uniqid()]);

        $faltantes = $proyecto->camposObligatoriosFaltantes();

        foreach (['objetivo general', 'definición del problema', 'entidad contraparte con instrumento de formalización', 'objetivos específicos con sus resultados', 'aporte institucional'] as $esperado) {
            $this->assertContains($esperado, $faltantes);
        }

        $proyecto->update(['objetivo_general' => 'Objetivo', 'definicion_problema' => 'Problema']);
        $faltantes = $proyecto->fresh()->camposObligatoriosFaltantes();

        $this->assertNotContains('objetivo general', $faltantes);
        $this->assertNotContains('definición del problema', $faltantes);
    }

    public function test_guardar_borrador_no_firma_al_coordinador(): void
    {
        $this->crearCargoCoordinador();
        [$component, $proyecto] = $this->componenteConProyecto();

        $component->borrador();

        $this->assertFalse(
            FirmaProyecto::query()
                ->where('firmable_type', Proyecto::class)
                ->where('firmable_id', $proyecto->id)
                ->whereNotNull('fecha_firma')
                ->exists()
        );
        $this->assertSame('Borrador guardado', collect(session('flash_notifications'))->last()['title']);
    }

    public function test_pdf_marca_campos_obligatorios_vacios_y_el_borrador(): void
    {
        [, $proyecto] = $this->componenteConProyecto();

        $html = view('components.fichas.ficha-proyecto-vinculacion', [
            'proyecto' => $proyecto->fresh(),
            'isPdf' => true,
        ])->render();

        $this->assertStringContainsString('CAMPO OBLIGATORIO NO REGISTRADO', $html);
        $this->assertStringContainsString('BORRADOR — NO VÁLIDO', $html);
        $this->assertStringNotContainsString('Sin objetivo general especificado', $html);
    }

    /** @return array{0: CreateProyectoVinculacion, 1: Proyecto} */
    private function componenteConProyecto(): array
    {
        $usuario = User::create([
            'name' => 'Coordinador '.uniqid(),
            'email' => 'coordinador-'.uniqid().'@test.local',
        ]);
        $empleado = Empleado::create([
            'nombre_completo' => 'Coordinador de prueba',
            'numero_empleado' => 'AUTO-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $usuario->id,
        ]);
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto autoguardado '.uniqid()]);
        $proyecto->coordinador_proyecto()->create(['empleado_id' => $empleado->id, 'rol' => 'Coordinador']);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Borrador'])->id,
            'fecha' => now(),
            'comentario' => 'Borrador de prueba',
            'es_actual' => true,
        ]);

        $this->actingAs($usuario->fresh('empleado'));

        $component = new CreateProyectoVinculacion;
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;
        $component->nombre_proyecto = $proyecto->nombre_proyecto;

        return [$component, $proyecto];
    }

    private function crearCargoCoordinador(): void
    {
        $tipo = TipoCargoFirma::firstOrCreate(['nombre' => 'Coordinador Proyecto']);
        CargoFirma::firstOrCreate([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipo->id,
        ]);
    }
}
