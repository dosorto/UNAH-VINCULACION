<?php

namespace Tests\Feature;

use App\Concerns\ReenviaDesdeSubsanacionPorEtapa;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProyectoVinculacionSubsanacionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guardados_y_anexos_conservan_subsanacion_y_el_historial_del_rechazo(): void
    {
        $contexto = $this->contextoSubsanacion();
        $proyecto = $contexto['proyecto'];
        $estadoSubsanacion = $proyecto->estado;
        $cantidadEstados = $proyecto->estado_proyecto()->count();
        $cantidadFirmas = $proyecto->firma_proyecto()->count();

        $component = new CreateProyectoVinculacion;
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;
        $component->nombre_proyecto = 'Proyecto corregido durante subsanación';
        $component->autoGuardarBorrador();

        $proyecto->anexos()->create(['documento_url' => 'anexos/prueba-subsanacion.pdf']);

        $this->assertSame('Subsanacion', $proyecto->fresh()->estadoDespuesDeGuardar());
        $this->assertSame($estadoSubsanacion->id, $proyecto->fresh()->estado->id);
        $this->assertSame($cantidadEstados, $proyecto->estado_proyecto()->count());
        $this->assertSame($cantidadFirmas, $proyecto->firma_proyecto()->count());
        $this->assertSame('Rechazado', $contexto['firma']->fresh()->estado_revision);
        $this->assertSame('Debe corregir el alcance comunitario.', $estadoSubsanacion->fresh()->comentario);
    }

    public function test_guardar_como_borrador_no_degrada_subsanacion_y_registra_el_guardado(): void
    {
        $contexto = $this->contextoSubsanacion();
        $this->crearCargoCoordinador();
        $component = new CreateProyectoVinculacion;
        $component->recordId = $contexto['proyecto']->id;
        $component->proyectoId = $contexto['proyecto']->id;
        $component->nombre_proyecto = $contexto['proyecto']->nombre_proyecto;

        $component->borrador();

        $this->assertSame('Subsanacion', $contexto['proyecto']->fresh()->estadoDespuesDeGuardar());
        $this->assertTrue(Activity::query()
            ->where('subject_type', Proyecto::class)
            ->where('subject_id', $contexto['proyecto']->id)
            ->where('description', 'Cambios guardados durante la subsanación.')
            ->exists());
    }

    public function test_guardar_es_neutral_para_borrador_revision_y_aprobado(): void
    {
        foreach (['Borrador', 'En revision', 'Aprobado'] as $nombre) {
            $proyecto = Proyecto::create(['nombre_proyecto' => 'Estado '.$nombre.' '.uniqid()]);
            $proyecto->estado_proyecto()->create([
                'empleado_id' => $this->empleado('Actor '.$nombre)->id,
                'tipo_estado_id' => $this->estado($nombre)->id,
                'fecha' => now(),
                'comentario' => 'Estado de prueba',
                'es_actual' => true,
            ]);

            $component = new CreateProyectoVinculacion;
            $component->recordId = $proyecto->id;
            $component->proyectoId = $proyecto->id;
            $component->nombre_proyecto = 'Contenido actualizado';
            $component->autoGuardarBorrador();

            $this->assertSame($nombre, $proyecto->fresh()->estadoDespuesDeGuardar());
        }
    }

    public function test_interfaz_cambia_textos_y_muestra_detalle_de_subsanacion(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('Este proyecto está en subsanación.', $vista);
        $this->assertStringContainsString('$enSubsanacion ? \'Guardar cambios\' : \'Guardar como Borrador\'', $vista);
        $this->assertStringContainsString('$enSubsanacion ? \'Reenviar a revisión\' : \'Enviar para Firmar\'', $vista);
        $this->assertStringContainsString("\$detalleSubsanacion['motivo']", $vista);
        $this->assertStringContainsString("\$detalleSubsanacion['rechazado_por']", $vista);
        $this->assertStringContainsString("\$detalleSubsanacion['etapa']", $vista);
    }

    public function test_reenvio_conserva_ciclo_y_motivo_crea_nuevo_ciclo_y_pasa_a_revision(): void
    {
        $contexto = $this->contextoSubsanacion();
        $harness = new ReenvioSubsanacionHarness($contexto['proyecto']->id);

        $firmas = $harness->reenviar(
            $contexto['firma'],
            $contexto['usuario'],
            [$contexto['etapa']->id => $contexto['revisor']->id]
        );

        $this->assertCount(1, $firmas);
        $this->assertSame(2, $firmas->first()->revision_ciclo);
        $this->assertSame('Pendiente', $firmas->first()->estado_revision);
        $this->assertSame('Rechazado', $contexto['firma']->fresh()->estado_revision);
        $this->assertSame('En revision', $contexto['proyecto']->fresh()->estadoDespuesDeGuardar());
        $this->assertDatabaseHas('estado_proyecto', [
            'id' => $contexto['estado_subsanacion']->id,
            'comentario' => 'Debe corregir el alcance comunitario.',
        ]);
        $this->assertSame($contexto['etapa']->id, $firmas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($contexto['revisor']->id, $firmas->first()->empleado_id);
    }

    public function test_borrador_historico_degradado_se_detecta_y_repara_solo_al_reenviar(): void
    {
        $contexto = $this->contextoSubsanacion();
        $contexto['proyecto']->estado_proyecto()->create([
            'empleado_id' => $contexto['coordinador']->id,
            'tipo_estado_id' => $this->estado('Borrador')->id,
            'fecha' => now(),
            'comentario' => 'Guardado como borrador',
            'es_actual' => true,
        ]);

        $this->assertFalse($contexto['proyecto']->fresh()->estaEnSubsanacionActiva());
        $this->assertTrue($contexto['proyecto']->fresh()->puedeRepararSubsanacionDegradada());

        $harness = new ReenvioSubsanacionHarness($contexto['proyecto']->id);
        $harness->reenviar(
            $contexto['firma'],
            $contexto['usuario'],
            [$contexto['etapa']->id => $contexto['revisor']->id]
        );

        $this->assertSame('En revision', $contexto['proyecto']->fresh()->estadoDespuesDeGuardar());
        $this->assertDatabaseHas('estado_proyecto', [
            'estadoable_type' => Proyecto::class,
            'estadoable_id' => $contexto['proyecto']->id,
            'tipo_estado_id' => $this->estado('Subsanacion')->id,
            'comentario' => 'Estado Subsanación restaurado de forma segura. Motivo original: Debe corregir el alcance comunitario.',
        ]);
        $this->assertTrue(Activity::query()
            ->where('subject_type', Proyecto::class)
            ->where('subject_id', $contexto['proyecto']->id)
            ->where('description', 'Se corrigió un estado degradado por un guardado durante subsanación.')
            ->exists());
    }

    private function contextoSubsanacion(): array
    {
        $usuario = User::create([
            'name' => 'Coordinador '.uniqid(),
            'email' => 'coordinador-'.uniqid().'@test.local',
        ]);
        $permiso = Permission::firstOrCreate([
            'name' => 'docente.crear-proyecto',
            'guard_name' => 'web',
        ]);
        $usuario->givePermissionTo($permiso);
        $coordinador = $this->empleado('Coordinador', $usuario);
        $revisor = $this->empleado('Revisor');
        $flujo = FlujoAprobacion::create([
            'codigo' => 'SUBSANACION_'.uniqid(),
            'nombre' => 'Flujo de subsanación',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $cargo = $this->cargo('Revisión de vinculación', $this->estado('En revision')->id);
        $etapa = $flujo->etapas()->create([
            'orden' => 1,
            'codigo' => 'REVISION_'.uniqid(),
            'nombre' => 'Revisión técnica',
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
            'aplica_proyecto_inscripcion' => true,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
        ]);
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto en subsanación '.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        $proyecto->coordinador_proyecto()->create([
            'empleado_id' => $coordinador->id,
            'rol' => 'Coordinador',
        ]);
        $firma = $proyecto->firma_proyecto()->create([
            'empleado_id' => $revisor->id,
            'cargo_firma_id' => $cargo->id,
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => 1,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'revision_ciclo' => 1,
            'estado_revision' => 'Rechazado',
            'fecha_firma' => now(),
            'hash' => 'rechazo-'.uniqid(),
        ]);
        $estadoSubsanacion = $proyecto->estado_proyecto()->create([
            'empleado_id' => $revisor->id,
            'tipo_estado_id' => $this->estado('Subsanacion')->id,
            'fecha' => now(),
            'comentario' => 'Debe corregir el alcance comunitario.',
            'es_actual' => true,
        ]);

        $this->actingAs($usuario->fresh('empleado'));

        return compact(
            'usuario',
            'coordinador',
            'revisor',
            'flujo',
            'cargo',
            'etapa',
            'proyecto',
            'firma',
            'estadoSubsanacion'
        ) + ['estado_subsanacion' => $estadoSubsanacion];
    }

    private function estado(string $nombre): TipoEstado
    {
        return TipoEstado::firstOrCreate(['nombre' => $nombre]);
    }

    private function empleado(string $nombre, ?User $usuario = null): Empleado
    {
        $usuario ??= User::create([
            'name' => $nombre.' '.uniqid(),
            'email' => strtolower($nombre).'-'.uniqid().'@test.local',
        ]);

        return Empleado::create([
            'nombre_completo' => $nombre,
            'numero_empleado' => 'SUB-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $usuario->id,
        ]);
    }

    private function cargo(string $nombre, int $tipoEstadoId): CargoFirma
    {
        $tipo = TipoCargoFirma::create(['nombre' => $nombre.' '.uniqid()]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
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

class ReenvioSubsanacionHarness
{
    use ReenviaDesdeSubsanacionPorEtapa;

    public function __construct(private readonly int $proyectoId)
    {
    }

    public function reenviar(FirmaProyecto $firma, User $usuario, array $empleadosPorEtapa): Collection
    {
        return $this->reenviarDesdeSubsanacionPorEtapa($firma, $usuario, $empleadosPorEtapa);
    }

    protected function proyectoEsperadoIdParaReenvio(): ?int
    {
        return $this->proyectoId;
    }
}
