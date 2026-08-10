<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;
use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectoLegacyWorkflowAdoptionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_adopta_un_proyecto_en_revision_desde_su_etapa_actual_sin_recrear_las_anteriores(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 2, 'En revision');
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $diagnostico = $service->diagnosticar($contexto['proyecto'], $contexto['flujo']);

        $this->assertSame(ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION, $diagnostico['modo']);
        $this->assertSame($contexto['etapas'][1]->id, $diagnostico['etapa_inicio_id']);
        $this->assertSame($contexto['usuarios'][1]->id, collect($diagnostico['etapas'])->firstWhere('id', $contexto['etapas'][1]->id)['propuesto_usuario_id']);

        $adopcion = $service->adoptar(
            $contexto['proyecto'],
            $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][1]->id,
            [
                $contexto['etapas'][1]->id => $contexto['usuarios'][1]->id,
                $contexto['etapas'][2]->id => $contexto['usuarios'][2]->id,
            ],
            $contexto['actor']
        );

        $firmas = $contexto['proyecto']->firma_proyecto()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->orderBy('orden_revision')
            ->get();

        $this->assertSame($contexto['flujo']->id, $contexto['proyecto']->fresh()->flujo_aprobacion_id);
        $this->assertSame(2, $adopcion->orden_inicio);
        $this->assertSame($contexto['usuarios'][1]->id, $adopcion->revisor_usuario_id);
        $this->assertSame([2, 3], $firmas->pluck('orden_revision')->all());
        $this->assertSame(['Pendiente', 'Pendiente'], $firmas->pluck('estado_revision')->all());
        $this->assertSame($contexto['usuarios'][1]->id, $firmas->first()->responsable_usuario_id);
        $this->assertSame(0, $contexto['proyecto']->firma_proyecto()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->where('orden_revision', 1)
            ->count());
        $this->assertSame(3, $contexto['proyecto']->firma_proyecto()
            ->whereNull('flujo_aprobacion_etapa_id')
            ->where('estado_revision', 'Anulado')
            ->count());
        $this->assertTrue($contexto['proyecto']->fresh()->firmaEsActualEnFlujoPorEtapa($firmas->first()));
        Mail::assertQueued(EtapaFlujoPendiente::class, function (EtapaFlujoPendiente $mail) use ($contexto): bool {
            return $mail->hasTo($contexto['usuarios'][1]->email)
                && (int) $mail->etapa->id === (int) $contexto['etapas'][1]->id;
        });
        Mail::assertQueuedCount(1);
    }

    public function test_adopta_una_subsanacion_y_el_reenvio_retoma_al_mismo_revisor(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 2, 'Subsanacion', true);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);

        $adopcion = $service->adoptar(
            $contexto['proyecto'],
            $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION,
            $contexto['etapas'][1]->id,
            [
                $contexto['etapas'][1]->id => $contexto['usuarios'][1]->id,
                $contexto['etapas'][2]->id => $contexto['usuarios'][2]->id,
            ],
            $contexto['actor'],
            'Corregir la documentación indicada por el revisor legacy.'
        );

        $cicloUno = $contexto['proyecto']->firmasDeEtapasDelFlujo($contexto['flujo']->id, 1);
        $this->assertSame(['Rechazado', 'Pendiente'], $cicloUno->pluck('estado_revision')->all());
        $this->assertSame($contexto['usuarios'][1]->id, $cicloUno->first()->responsable_usuario_id);
        $this->assertSame($contexto['usuarios'][1]->id, $adopcion->revisor_usuario_id);
        $this->assertTrue($contexto['proyecto']->fresh()->tieneEvidenciaSubsanacionActiva());
        Mail::assertNothingQueued();

        $cicloDos = $contexto['proyecto']->crearNuevoCicloDesdeFirmaRechazada($cicloUno->first(), [
            $contexto['etapas'][1]->id => $contexto['empleados'][1]->id,
            $contexto['etapas'][2]->id => $contexto['empleados'][2]->id,
        ]);

        $this->assertSame([2, 3], $cicloDos->pluck('orden_revision')->all());
        $this->assertSame(['Pendiente', 'Pendiente'], $cicloDos->pluck('estado_revision')->all());
        $this->assertSame($contexto['usuarios'][1]->id, $cicloDos->first()->responsable_usuario_id);
        $this->assertSame(2, $cicloDos->first()->revision_ciclo);
    }

    public function test_borrador_solo_fija_el_flujo_y_la_adopcion_no_puede_duplicarse(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(2, null, 'Autoguardado', false, false);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);

        $adopcion = $service->adoptar(
            $contexto['proyecto'],
            $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_BORRADOR,
            null,
            [],
            $contexto['actor']
        );

        $this->assertSame(ProyectoLegacyWorkflowAdoptionService::MODO_BORRADOR, $adopcion->modo);
        $this->assertNull($adopcion->etapa_inicio_id);
        $this->assertSame($contexto['flujo']->id, $contexto['proyecto']->fresh()->flujo_aprobacion_id);
        $this->assertSame(0, $contexto['proyecto']->firma_proyecto()->count());
        Mail::assertNothingQueued();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ya fue adoptado');

        $service->adoptar(
            $contexto['proyecto']->fresh(),
            $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_BORRADOR,
            null,
            [],
            $contexto['actor']
        );
    }

    public function test_detecta_la_primera_etapa_no_aprobada_por_la_secuencia_legacy(): void
    {
        $contexto = $this->crearContexto(3, null, 'Revision legacy sin cargo '.uniqid(), false, false);

        foreach ([0, 1] as $index) {
            $contexto['proyecto']->firma_proyecto()->create([
                'empleado_id' => $contexto['empleados'][$index]->id,
                'cargo_firma_id' => $contexto['cargos'][$index]->id,
                'estado_revision' => 'Aprobado',
                'hash' => 'legacy-approved-'.uniqid(),
            ]);
        }

        $diagnostico = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar(
            $contexto['proyecto']->fresh(),
            $contexto['flujo']
        );

        $this->assertSame($contexto['etapas'][2]->id, $diagnostico['etapa_inicio_id']);
        $this->assertStringContainsString('primera etapa pendiente', $diagnostico['razon_etapa']);
    }

    public function test_bloquea_si_el_flujo_termina_antes_del_estado_legacy_actual(): void
    {
        $contexto = $this->crearContexto(2, null, 'Revision legacy sin cargo '.uniqid(), false, false);

        foreach ([0, 1] as $index) {
            $contexto['proyecto']->firma_proyecto()->create([
                'empleado_id' => $contexto['empleados'][$index]->id,
                'cargo_firma_id' => $contexto['cargos'][$index]->id,
                'estado_revision' => 'Aprobado',
                'hash' => 'legacy-approved-'.uniqid(),
            ]);
        }

        $diagnostico = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar(
            $contexto['proyecto']->fresh(),
            $contexto['flujo']
        );

        $this->assertNull($diagnostico['etapa_inicio_id']);
        $this->assertStringContainsString('Todas las etapas configuradas ya aparecen aprobadas', $diagnostico['razon_etapa']);
        $this->assertNotEmpty($diagnostico['bloqueos']);
    }

    public function test_el_motor_rechaza_una_etapa_distinta_de_la_detectada(): void
    {
        $contexto = $this->crearContexto(3, 2, 'En revision');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no coincide con la etapa detectada automáticamente');

        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $contexto['proyecto'],
            $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][2]->id,
            [
                $contexto['etapas'][1]->id => $contexto['usuarios'][1]->id,
                $contexto['etapas'][2]->id => $contexto['usuarios'][2]->id,
            ],
            $contexto['actor']
        );
    }

    public function test_la_bandeja_muestra_el_diagnostico_y_el_recorrido_que_continuara(): void
    {
        $contexto = $this->crearContexto(3, 2, 'En revision');
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $rolAdmin->givePermissionTo($permiso);
        $contexto['actor']->assignRole($rolAdmin);
        $this->actingAs($contexto['actor']);

        Livewire::test(ListProyectosVinculacion::class)
            ->call('openFlowModal', $contexto['proyecto']->id)
            ->set('flowSelectedId', $contexto['flujo']->id)
            ->assertSet('flowIsLegacyAdoption', true)
            ->assertSet('flowAdoptionMode', ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION)
            ->assertSet('flowStartStageId', $contexto['etapas'][1]->id)
            ->assertSee('Diagnóstico legacy')
            ->assertSee('Completadas antes de la adopción')
            ->assertSee('Responsables del recorrido que continúa')
            ->assertSee('Situación detectada')
            ->assertSee('Etapa actual / etapa de retorno detectada')
            ->assertSee('no admite selección manual')
            ->assertDontSee('Seleccione la etapa...')
            ->assertSee('Buscar y seleccionar revisor...')
            ->assertSee('Buscar por nombre o correo...')
            ->assertSee('Etapa 2')
            ->assertSee('Etapa 3')
            ->set('flowStartStageId', $contexto['etapas'][2]->id)
            ->assertSet('flowStartStageId', $contexto['etapas'][1]->id)
            ->set('flowAdoptionMode', ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO)
            ->assertSet('flowAdoptionMode', ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION);
    }

    public function test_la_bandeja_explica_roles_faltantes_y_actualiza_los_usuarios_elegibles(): void
    {
        $contexto = $this->crearContexto(2, 1, 'En revision');
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $rolAdmin->givePermissionTo($permiso);
        $contexto['actor']->assignRole($rolAdmin);
        $this->actingAs($contexto['actor']);

        $rolSegundaEtapa = $contexto['etapas'][1]->rolRevisor;
        $contexto['usuarios'][1]->removeRole($rolSegundaEtapa);

        $component = Livewire::test(ListProyectosVinculacion::class)
            ->call('openFlowModal', $contexto['proyecto']->id)
            ->set('flowSelectedId', $contexto['flujo']->id)
            ->assertSee('No hay usuarios disponibles para esta etapa.')
            ->assertSee('Actualizar usuarios')
            ->assertSee($rolSegundaEtapa->name);

        $contexto['usuarios'][1]->assignRole($rolSegundaEtapa);

        $component
            ->call('refreshFlowReviewerCandidates')
            ->assertSee('Revisor de etapa 2')
            ->assertSee('Puede buscar por nombre o correo.');
    }

    public function test_la_bandeja_no_pide_seleccionar_un_responsable_fijo_definido_en_el_flujo(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(1, 1, 'En revision');
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $rolAdmin->givePermissionTo($permiso);
        $contexto['actor']->assignRole($rolAdmin);
        $contexto['etapas'][0]->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => $contexto['usuarios'][0]->id,
        ]);
        $this->actingAs($contexto['actor']);

        Livewire::test(ListProyectosVinculacion::class)
            ->call('openFlowModal', $contexto['proyecto']->id)
            ->set('flowSelectedId', $contexto['flujo']->id)
            ->assertSee('Asignado automáticamente según la configuración del flujo.')
            ->assertSee($contexto['usuarios'][0]->email)
            ->assertDontSee('Buscar y seleccionar revisor...')
            ->set('flowReviewers.'.$contexto['etapas'][0]->id, $contexto['actor']->id)
            ->call('saveFlow')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('firma_proyecto', [
            'firmable_type' => Proyecto::class,
            'firmable_id' => $contexto['proyecto']->id,
            'flujo_aprobacion_etapa_id' => $contexto['etapas'][0]->id,
            'responsable_usuario_id' => $contexto['usuarios'][0]->id,
        ]);
    }

    private function crearContexto(
        int $cantidadEtapas,
        ?int $etapaActual,
        string $estadoActual,
        bool $rechazada = false,
        bool $crearFirmasLegacy = true
    ): array {
        $actor = User::factory()->create();
        $actorEmpleado = Empleado::create([
            'nombre_completo' => 'Administrador de adopción',
            'numero_empleado' => 'ADM-'.uniqid(),
            'user_id' => $actor->id,
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'LEGACY_TEST_'.strtoupper(uniqid()),
            'nombre' => 'Flujo de prueba legacy',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto legacy de prueba']);
        $etapas = collect();
        $usuarios = collect();
        $empleados = collect();
        $cargos = collect();
        $estados = collect();

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $rol = Role::create([
                'name' => 'Revisor Legacy '.$orden.' '.uniqid(),
                'guard_name' => 'web',
            ]);
            $usuario = User::factory()->create();
            $usuario->assignRole($rol);
            $usuario->forceFill(['active_role_id' => $rol->id])->save();
            $empleado = Empleado::create([
                'nombre_completo' => 'Revisor de etapa '.$orden,
                'numero_empleado' => 'REV-'.$orden.'-'.uniqid(),
                'user_id' => $usuario->id,
            ]);
            $estado = TipoEstado::create(['nombre' => $etapaActual === $orden && $estadoActual !== 'Subsanacion'
                ? $estadoActual
                : 'Estado etapa '.$orden.' '.uniqid()]);
            $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo legacy '.$orden.' '.uniqid()]);
            $cargo = CargoFirma::create([
                'descripcion' => 'Proyecto',
                'tipo_cargo_firma_id' => $tipoCargo->id,
                'tipo_estado_id' => $estado->id,
                'estado_siguiente_id' => $estado->id,
            ]);
            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'ETAPA_'.$orden,
                'nombre' => 'Etapa '.$orden,
                'tipo_etapa' => 'APROBACION',
                'rol_revisor_id' => $rol->id,
                'cargo_firma_id' => $cargo->id,
                'aplica_inscripcion' => true,
                'activo' => true,
            ]);

            if ($crearFirmasLegacy) {
                $proyecto->firma_proyecto()->create([
                    'empleado_id' => $empleado->id,
                    'cargo_firma_id' => $cargo->id,
                    'estado_revision' => $rechazada && $etapaActual === $orden ? 'Rechazado' : 'Pendiente',
                    'hash' => 'legacy-'.$orden.'-'.uniqid(),
                ]);
            }

            $usuarios->push($usuario);
            $empleados->push($empleado);
            $cargos->push($cargo);
            $estados->push($estado);
            $etapas->push($etapa);
        }

        $estadoProyecto = $estadoActual === 'Subsanacion'
            ? TipoEstado::firstOrCreate(['nombre' => 'Subsanacion'])
            : ($etapaActual ? $estados[$etapaActual - 1] : TipoEstado::create(['nombre' => $estadoActual]));

        EstadoProyecto::withoutEvents(fn () => $proyecto->estado_proyecto()->create([
            'empleado_id' => $actorEmpleado->id,
            'tipo_estado_id' => $estadoProyecto->id,
            'fecha' => now(),
            'comentario' => $estadoActual === 'Subsanacion' ? 'Motivo legacy conservado.' : 'Estado legacy.',
            'es_actual' => true,
        ]));

        return compact('actor', 'flujo', 'proyecto', 'etapas', 'usuarios', 'empleados', 'cargos', 'estados');
    }
}
