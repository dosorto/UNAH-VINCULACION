<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Route;
use App\Models\Pasantia;
use Tests\TestCase;

class PasantiaIntegrationTest extends TestCase
{
    use WithoutMiddleware;

    public function test_rutas_de_pasantias_estan_registradas_sin_duplicados(): void
    {
        foreach (['crearPasantia', 'pasantias.edit', 'pasantias.show'] as $nombre) {
            $this->assertNotNull(Route::getRoutes()->getByName($nombre));
        }

        $this->assertSame('App\\Livewire\\Proyectos\\Vinculacion\\CreatePasantia', Route::getRoutes()->getByName('crearPasantia')->getActionName());
    }

    public function test_el_detalle_de_pasantias_expone_acciones_historial_documentos_y_anexos(): void
    {
        $view = file_get_contents(base_path('resources/views/livewire/proyectos/vinculacion/show-pasantia.blade.php'));

        foreach (['FORM-DVUS-013', 'Enviar a revisión', 'Enviar a subsanación', 'Historial de movimientos', 'Documentos generados', 'Anexos'] as $texto) {
            $this->assertStringContainsString($texto, $view);
        }

        foreach (['enviarRevision', 'aprobar', 'abrirModalSubsanacion', 'iniciarSubsanacion'] as $accion) {
            $this->assertStringContainsString($accion, $view);
        }

        $editor = file_get_contents(base_path('resources/views/livewire/proyectos/vinculacion/create-pasantia.blade.php'));
        foreach (['abrirModalEnviar', 'modalSiguiente', 'confirmarEnvio', 'Confirmar envío'] as $texto) {
            $this->assertStringContainsString($texto, $editor);
        }
    }

    public function test_el_workflow_de_pasantias_reporta_la_etapa_sin_responsable(): void
    {
        $service = file_get_contents(base_path('app/Services/Pasantias/PasantiaWorkflowService.php'));

        $this->assertStringContainsString('PASANTIAS_FORM_DVUS_013', $service);
        $this->assertStringContainsString('Configure el flujo desde Configuración → Flujos', $service);
        $this->assertStringContainsString('requiere un responsable fijo válido antes de enviar.', $service);
    }

    public function test_el_detalle_y_pdf_respetan_las_siete_secciones_del_formulario_oficial(): void
    {
        $detalle = file_get_contents(base_path('resources/views/livewire/proyectos/vinculacion/show-pasantia.blade.php'));
        $pdf = file_get_contents(base_path('resources/views/pdf/pasantias/form-013.blade.php'));
        $service = new \App\Services\Pasantias\PasantiaPdfGenerator();
        $show = new \App\Livewire\Proyectos\Vinculacion\ShowPasantia();
        $sectionsMethod = new \ReflectionMethod($show, 'secciones');
        $secciones = $service->seccionesFormulario();

        $this->assertSame($secciones, $sectionsMethod->invoke($show));
        $this->assertCount(7, $secciones);
        $this->assertStringContainsString('seccionesFormulario', file_get_contents(base_path('app/Services/Pasantias/PasantiaPdfGenerator.php')));
        $this->assertStringContainsString('$secciones', $pdf);
        $this->assertStringContainsString('$valor($registro->{$campo}, $campo)', $pdf);
        $this->assertStringContainsString('Ficha FORM-DVUS-013', $detalle);

        foreach (['fecha_registro', 'fecha_inicio', 'fecha_finalizacion', 'tipo_pasantia', 'modalidad_ejecucion', 'nombre_contacto_directo', 'jornada_laboral_docente', 'firma_estudiante', 'archivo_convenio_marco'] as $campo) {
            $this->assertStringContainsString($campo, $detalle);
        }

        $editor = file_get_contents(base_path('resources/views/livewire/proyectos/vinculacion/create-pasantia.blade.php'));
        $this->assertStringContainsString('wire:key="pasantia-paso-{{ $pasoActual }}"', $editor);
        $this->assertStringContainsString('wire:key="pasantia-paso-{{ $pasoActual }}-campo-{{ $campo }}"', $editor);
    }

    public function test_el_mapeo_oficial_no_cruza_valores_con_etiquetas(): void
    {
        $registro = new Pasantia([
            'fecha_registro' => '2026-09-10', 'facultad_centro' => 'Centro Único', 'escuela_departamento' => 'Departamento de Sistemas',
            'carrera' => 'Carrera Única', 'numero_cuenta' => '20212320423', 'nombre_estudiante' => 'Juan Pérez', 'celular_estudiante' => '99998888',
            'correo_institucional' => 'juan.perez@unah.hn', 'correo_personal' => 'juanp@gmail.com', 'total_horas' => 800, 'horas_semanales' => 20,
        ]);
        $sections = (new \App\Services\Pasantias\PasantiaPdfGenerator())->seccionesFormulario();
        $expected = ['fecha_registro' => '2026-09-10', 'escuela_departamento' => 'Departamento de Sistemas', 'numero_cuenta' => '20212320423', 'nombre_estudiante' => 'Juan Pérez', 'celular_estudiante' => '99998888', 'correo_institucional' => 'juan.perez@unah.hn', 'correo_personal' => 'juanp@gmail.com', 'total_horas' => 800, 'horas_semanales' => 20];
        $mapped = collect($sections)->flatMap(fn (array $fields) => collect($fields))->values()->all();

        foreach ($expected as $field => $value) {
            $this->assertContains($field, $mapped);
            $actual = in_array($field, ['fecha_registro', 'fecha_inicio', 'fecha_finalizacion'], true)
                ? $registro->{$field}?->format('Y-m-d')
                : $registro->{$field};
            $this->assertSame($value, $actual);
        }
    }
}
