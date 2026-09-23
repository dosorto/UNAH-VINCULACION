<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;
use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService;
use App\Services\Proyecto\ProyectoWorkflowService;
use App\Services\InformeIntermedio\InformeIntermedioProyectoWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectoLegacyWorkflowAdoptionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_proyecto_nuevo_recorre_etapas_con_estado_general_y_termina_registrado(): void
    {
        $this->verificarRecorridoEnviado(false);
    }

    public function test_retirar_etapas_no_interrumpe_bandeja_ni_aprobacion_de_un_proyecto_enviado(): void
    {
        $this->verificarRecorridoEnviado(true);
    }

    private function verificarRecorridoEnviado(bool $retirarEtapas): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, null, 'Borrador', crearFirmasLegacy: false);
        $proyecto = $contexto['proyecto'];
        $proyecto->update(['flujo_aprobacion_id' => $contexto['flujo']->id]);
        foreach ($contexto['etapas'] as $index => $etapa) {
            $etapa->update(['usuario_responsable_id' => $contexto['usuarios'][$index]->id]);
        }
        $tipoCargo = TipoCargoFirma::firstOrCreate(['nombre' => 'Coordinador Proyecto']);
        CargoFirma::firstOrCreate(['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $tipoCargo->id]);
        $this->actingAs($contexto['actor']);
        $componente = new \App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
        (new \ReflectionMethod($componente, 'enviarPorFlujoDeEtapas'))->invoke($componente, $proyecto);

        $this->assertSame('En revision', $proyecto->fresh()->estado->tipoestado->nombre);
        $firmas = $proyecto->firmasDeEtapasDelFlujo($contexto['flujo']->id);
        $this->assertCount(3, $firmas);
        $this->assertFalse($proyecto->adopcionFlujoLegacy()->exists());
        if ($retirarEtapas) {
            $configuracion = new \App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
            (new \ReflectionMethod($configuracion, 'syncFlowStages'))->invoke($configuracion, $contexto['flujo'], []);
            $this->assertCount(0, $contexto['flujo']->fresh()->etapas);
            $this->assertCount(3, $proyecto->fresh()->etapasParaStepper());
        }
        foreach ($firmas as $index => $firma) {
            $this->assertSame('En revision', $proyecto->fresh()->estado->tipoestado->nombre);
            $this->actingAs($contexto['usuarios'][$index]);
            $bandeja = new \App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
            $query = (new \ReflectionMethod($bandeja, 'firmasDisponiblesQuery'))->invoke($bandeja);
            $this->assertContains($firma->id, $query->pluck('firma_proyecto.id')->all());
            $historial = new \App\Livewire\Docente\Proyectos\HistorialProyecto;
            $historial->proyecto = $proyecto->fresh();
            $this->assertSame($firma->id, $historial->firmaPendienteRevision()?->id);
            $bandeja->aprobar($firma->id);
            $this->assertSame('Aprobado', $firma->fresh()->estado_revision);
        }
        $this->assertSame('Registrado', $proyecto->fresh()->estado->tipoestado->nombre);
        $this->assertTrue($proyecto->fresh()->estaRegistrado());
    }

    public function test_listado_muestra_estado_y_etapa_del_recorrido_por_separado(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(2, 2, 'En revision');
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rolAdmin->givePermissionTo(Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']));
        $contexto['actor']->assignRole($rolAdmin);
        $contexto['proyecto']->update(['nombre_proyecto' => 'Resumen flujo '.uniqid()]);
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $contexto['proyecto'], $contexto['flujo'], ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][1]->id, [$contexto['etapas'][1]->id => $contexto['usuarios'][1]->id], $contexto['actor']
        );
        Livewire::actingAs($contexto['actor'])->test(ListProyectosVinculacion::class)
            ->set('search', $contexto['proyecto']->nombre_proyecto)
            ->assertSee('En revision')
            ->assertSee('Etapa pendiente:')
            ->assertSee('Etapa 2')
            ->assertSee($contexto['etapas'][1]->rolRevisor->name);
    }

    public function test_no_readapta_una_firma_que_perdio_su_etapa(): void
    {
        $contexto = $this->crearContexto(2, 2, 'En revision');
        $firma = $contexto['proyecto']->firma_proyecto()->first();
        $firma->update(['flujo_aprobacion_id' => $contexto['flujo']->id, 'revision_ciclo' => 1, 'etapa_nombre' => 'Etapa eliminada']);
        $diagnostico = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($contexto['proyecto'], $contexto['flujo']);
        $this->assertStringContainsString('etapa ya no está disponible', implode(' ', $diagnostico['bloqueos']));
    }

    public function test_adaptacion_con_tres_aprobaciones_conserva_firmas_y_solo_crea_la_cuarta(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(4, 4, 'En revision');
        $proyecto = $contexto['proyecto'];
        $proyecto->firma_proyecto()->whereIn('cargo_firma_id', $contexto['cargos']->take(3)->pluck('id'))
            ->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);
        $aprobadas = $proyecto->firma_proyecto()->where('estado_revision', 'Aprobado')->get()->toArray();
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $proyecto, $contexto['flujo'], ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][3]->id, [$contexto['etapas'][3]->id => $contexto['usuarios'][3]->id], $contexto['actor']
        );
        $this->assertSame($aprobadas, $proyecto->firma_proyecto()->where('estado_revision', 'Aprobado')->get()->toArray());
        $firmas = $proyecto->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->get();
        $this->assertCount(1, $firmas);
        $this->assertSame(4, $firmas->first()->orden_revision);
        $this->actingAs($contexto['usuarios'][3]);
        (new \App\Livewire\Docente\Proyectos\ProyectosPorFirmar)->aprobar($firmas->first()->id);
        $this->assertSame('Registrado', $proyecto->fresh()->estado->tipoestado->nombre);
        $this->assertSame($aprobadas, $proyecto->firma_proyecto()->legacyAutentica()->where('estado_revision', 'Aprobado')->get()->toArray());
    }

    public function test_adaptacion_bloquea_estado_que_repetiria_una_aprobacion(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 2, 'En revision');
        $proyecto = $contexto['proyecto'];
        $proyecto->firma_proyecto()->where('cargo_firma_id', $contexto['cargos'][1]->id)
            ->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);
        $antes = $proyecto->firma_proyecto()->get()->toArray();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $this->assertStringContainsString('repetiría', implode(' ', $service->diagnosticar($proyecto, $contexto['flujo'])['bloqueos']));
        try {
            $service->adoptar($proyecto, $contexto['flujo'], ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
                $contexto['etapas'][1]->id, [], $contexto['actor']);
            $this->fail('No debe adaptar una correspondencia contradictoria.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('repetiría', $exception->getMessage());
        }
        $this->assertSame($antes, $proyecto->firma_proyecto()->get()->toArray());
        $this->assertFalse($proyecto->adopcionFlujoLegacy()->exists());
    }

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

    public function test_no_adapta_proyectos_sin_envio_aunque_se_fuerce_el_modo(): void
    {
        Mail::fake();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        foreach (['Borrador', 'Autoguardado', 'PendienteInformacion', 'Pendiente informacion', ''] as $estado) {
            $contexto = $this->crearContexto(2, null, $estado, false, false);
            $proyecto = $contexto['proyecto'];
            $this->assertFalse($service->permiteAdaptacion($proyecto));
            foreach ([ProyectoLegacyWorkflowAdoptionService::MODO_BORRADOR, ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION] as $modo) {
                try {
                    $service->adoptar($proyecto, $contexto['flujo'], $modo, null, [], $contexto['actor']);
                    $this->fail('No debe adaptar un expediente sin envío.');
                } catch (\RuntimeException $exception) {
                    $this->assertStringContainsString('no han sido enviados', $exception->getMessage());
                }
                $this->assertNull($proyecto->fresh()->flujo_aprobacion_id);
                $this->assertFalse($proyecto->adopcionFlujoLegacy()->exists());
                $this->assertSame(0, $proyecto->firma_proyecto()->count());
            }
        }
        Mail::assertNothingQueued();
    }

    public function test_borrador_no_muestra_adaptar_y_rechaza_apertura_y_guardado_directos(): void
    {
        $contexto = $this->crearContexto(2, null, 'Autoguardado', false, false);
        $rol = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rol->givePermissionTo(Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']));
        $contexto['actor']->assignRole($rol);
        $proyecto = $contexto['proyecto'];
        $proyecto->update(['nombre_proyecto' => 'Borrador sin adopción '.uniqid()]);
        Livewire::actingAs($contexto['actor'])->test(ListProyectosVinculacion::class)
            ->set('search', $proyecto->nombre_proyecto)
            ->assertDontSee($proyecto->nombre_proyecto)
            ->assertDontSee('Adaptar flujo')
            ->call('openFlowModal', $proyecto->id)->assertForbidden();
        Livewire::actingAs($contexto['actor'])->test(ListProyectosVinculacion::class)
            ->set('flowProyectoId', $proyecto->id)
            ->set('flowSelectedId', $contexto['flujo']->id)
            ->set('flowAdoptionMode', ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION)
            ->call('saveFlow');
        $this->assertNull($proyecto->fresh()->flujo_aprobacion_id);
        $this->assertFalse($proyecto->adopcionFlujoLegacy()->exists());
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

    public function test_adopcion_intermedia_completa_inscripcion_y_habilita_informes_al_aprobar_el_recorrido_restante(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 2, 'En revision');
        $this->prepararInformes($contexto);
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $contexto['proyecto'], $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][1]->id,
            [$contexto['etapas'][1]->id => $contexto['usuarios'][1]->id,
                $contexto['etapas'][2]->id => $contexto['usuarios'][2]->id],
            $contexto['actor']
        );
        $workflow = app(ProyectoWorkflowService::class);
        $this->assertFalse($workflow->inscripcionCompletada($contexto['proyecto']->fresh()));

        foreach ($contexto['proyecto']->firmasDeEtapasDelFlujo($contexto['flujo']->id, 1) as $firma) {
            $this->actingAs($firma->responsableUsuario);
            (new \App\Livewire\Docente\Proyectos\ProyectosPorFirmar)->aprobar($firma->id);
            $this->assertSame('Aprobado', $firma->fresh()->estado_revision);
        }

        $proyecto = $contexto['proyecto']->fresh();
        $this->assertSame('Registrado', $proyecto->estado->tipoestado->nombre);
        $this->assertTrue($workflow->inscripcionCompletada($proyecto));
        $this->assertTrue(app(InformeIntermedioProyectoWorkflowService::class)->estaDisponible($proyecto, $contexto['actor']));
        $this->assertSame(2, $proyecto->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->count());
    }

    public function test_adopcion_completada_habilita_informes_sin_inventar_firmas(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, null, 'En curso', crearFirmasLegacy: false);
        $this->prepararInformes($contexto);
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $contexto['proyecto'], $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO, null, [], $contexto['actor']
        );

        $proyecto = $contexto['proyecto']->fresh();
        $this->assertTrue(app(ProyectoWorkflowService::class)->inscripcionCompletada($proyecto));
        $this->assertTrue(app(InformeIntermedioProyectoWorkflowService::class)->estaDisponible($proyecto, $contexto['actor']));
        $this->assertSame(0, $proyecto->firma_proyecto()->count());
        Mail::assertNothingQueued();

        $nueva = $contexto['etapas']->last()->replicate();
        $nueva->forceFill(['codigo' => 'ETAPA_NUEVA', 'nombre' => 'Etapa agregada después', 'orden' => 10])->save();
        // Cambiar el catálogo no invalida una inscripción ya completada/adoptada.
        $this->assertTrue(app(ProyectoWorkflowService::class)->inscripcionCompletada($proyecto->fresh()));
        $this->assertTrue($proyecto->fresh()->etapasParaStepper()->last()['adoptada_antes']);
        $configuracion = new \App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
        (new \ReflectionMethod($configuracion, 'syncFlowStages'))->invoke($configuracion, $contexto['flujo'], []);
        $this->assertTrue(app(ProyectoWorkflowService::class)->inscripcionCompletada($proyecto->fresh()));
        $this->assertNotContains($nueva->id, $proyecto->fresh()->etapasParaStepper()->pluck('etapa.id')->all());
    }

    public function test_adopcion_en_subsanacion_reenvia_y_completa_desde_cada_etapa_sin_repetir_las_anteriores(): void
    {
        Mail::fake();
        Permission::firstOrCreate(['name' => 'docente.crear-proyecto', 'guard_name' => 'web']);

        foreach ([1, 2, 3] as $ordenActual) {
            $contexto = $this->crearContexto(3, $ordenActual, 'Subsanacion', true);
            $this->prepararInformes($contexto);
            $contexto['actor']->givePermissionTo('docente.crear-proyecto');
            $revisores = $contexto['etapas']->slice($ordenActual - 1)
                ->mapWithKeys(fn ($etapa, $indice): array => [$etapa->id => $contexto['usuarios'][$indice]->id])->all();
            app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
                $contexto['proyecto'], $contexto['flujo'],
                ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION,
                $contexto['etapas'][$ordenActual - 1]->id,
                $revisores, $contexto['actor'], 'Corrección histórica pendiente.'
            );
            $this->actingAs($contexto['actor']);
            $historial = new \App\Livewire\Docente\Proyectos\HistorialProyecto;
            $historial->proyecto = $contexto['proyecto']->fresh();
            $historial->subsanarComentario = 'Correcciones completadas.';
            $historial->subsanar();

            $firmas = $contexto['proyecto']->firmasDeEtapasDelFlujo($contexto['flujo']->id, 2);
            $this->assertSame(range($ordenActual, 3), $firmas->pluck('orden_revision')->all());
            $this->assertSame($contexto['usuarios'][$ordenActual - 1]->id, $firmas->first()->responsable_usuario_id);
            foreach ($firmas as $firma) {
                $this->assertSame('En revision', $contexto['proyecto']->fresh()->estado->tipoestado->nombre);
                $this->actingAs($firma->responsableUsuario);
                (new \App\Livewire\Docente\Proyectos\ProyectosPorFirmar)->aprobar($firma->id);
                $this->assertSame('Aprobado', $firma->fresh()->estado_revision);
            }
            $this->assertTrue(app(ProyectoWorkflowService::class)->inscripcionCompletada($contexto['proyecto']->fresh()));
            $this->assertSame(0, $contexto['proyecto']->firma_proyecto()
                ->whereNotNull('flujo_aprobacion_etapa_id')->where('orden_revision', '<', $ordenActual)->count());
        }
    }

    public function test_etapas_historicas_adoptadas_se_identifican_por_evidencia_aunque_cambie_el_orden(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 2, 'En revision');
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $contexto['proyecto'], $contexto['flujo'],
            ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
            $contexto['etapas'][1]->id,
            [$contexto['etapas'][1]->id => $contexto['usuarios'][1]->id,
                $contexto['etapas'][2]->id => $contexto['usuarios'][2]->id],
            $contexto['actor']
        );
        $contexto['etapas'][0]->update(['orden' => 10]);
        $contexto['etapas'][2]->update(['orden' => 1]);

        foreach (['etapasParaStepper', 'firmasParaFicha'] as $metodo) {
            $filas = $contexto['proyecto']->fresh()->{$metodo}()->keyBy('etapa.id');
            $this->assertTrue($filas[$contexto['etapas'][0]->id]['adoptada_antes']);
            $this->assertFalse($filas[$contexto['etapas'][2]->id]['adoptada_antes']);
        }
    }

    public function test_subsanacion_no_retrocede_a_una_etapa_antigua_si_la_etapa_rechazada_no_esta_en_el_flujo(): void
    {
        Mail::fake();
        $contexto = $this->crearContexto(3, 1, 'En revision');
        $proyecto = $contexto['proyecto'];
        foreach ([$contexto['estados'][2]->id, TipoEstado::firstOrCreate(['nombre' => 'Subsanacion'])->id] as $estadoId) {
            $proyecto->estado_proyecto()->create([
                'empleado_id' => $contexto['actor']->empleado->id,
                'tipo_estado_id' => $estadoId, 'fecha' => now(), 'es_actual' => true,
            ]);
        }
        $proyecto->firma_proyecto()->where('cargo_firma_id', $contexto['cargos'][2]->id)->update(['estado_revision' => 'Rechazado']);
        $contexto['etapas'][2]->update(['activo' => false]);
        $diagnostico = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($proyecto->fresh(), $contexto['flujo']->fresh());

        $this->assertNull($diagnostico['etapa_inicio_id']);
        $this->assertNotEmpty($diagnostico['bloqueos']);
    }

    private function prepararInformes(array $contexto): void
    {
        $contexto['etapas']->last()->update(['aplica_informe_intermedio' => true, 'aplica_cierre_proyecto' => true]);
        EmpleadoProyecto::create([
            'proyecto_id' => $contexto['proyecto']->id,
            'empleado_id' => $contexto['actor']->empleado->id,
            'rol' => 'Coordinador',
        ]);
        TipoEstado::firstOrCreate(['nombre' => 'En curso']);
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
