<?php

namespace Tests\Feature;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Integración del motor con datos sintéticos en SQLite; nunca conecta a la BD de la aplicación. */
class ProyectoLegacyReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false, 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        foreach (['0001_01_01_000000_create_users_table.php', '2024_09_28_213707_create_permission_tables.php',
            '2024_10_14_200458_proyecto_tables.php', '2026_05_09_000001_add_flujos_aprobacion_to_proyecto.php',
            '2026_05_09_000002_add_flags_to_flujos_aprobacion_etapas.php',
            '2026_05_27_000001_add_process_flags_to_flujos_aprobacion_etapas.php',
            '2026_07_03_094942_add_workflow_stage_fields_to_firma_proyecto_table.php',
            '2026_08_07_000001_create_workflow_legacy_adoptions_table.php',
            '2026_08_20_000006_add_requiere_asignacion_snapshot_to_firma_proyecto.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('empleado', function (Blueprint $t): void {
            $t->id();
            $t->integer('user_id');
            $t->string('nombre_completo');
            $t->string('numero_empleado');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('firma_sello_empleado', function (Blueprint $t): void {
            $t->id();
            $t->integer('empleado_id');
            $t->string('tipo');
            $t->string('ruta_storage');
            $t->boolean('estado')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $t): void {
            $t->string('tipo_etapa')->default('APROBACION');
            $t->integer('rol_revisor_id')->nullable();
            $t->integer('usuario_responsable_id')->nullable();
            $t->boolean('configuracion_vigente')->default(true);
        });
        // SQLite representa el enum vigente de MySQL como texto sin CHECK legado.
        DB::statement('PRAGMA ignore_check_constraints = ON');
        Mail::fake();
    }

    private function context(bool $subsanacion = false): array
    {
        $flow = FlujoAprobacion::create(['codigo' => 'REAL', 'nombre' => 'Real', 'proceso' => 'PROYECTO', 'activo' => true]);
        $project = Proyecto::create(['nombre_proyecto' => 'Legacy']);
        $stages = collect();
        $users = collect();
        $legacy = collect();
        foreach (['Jefe Departamento', 'Enlace Vinculacion', 'Director centro'] as $index => $name) {
            $user = User::factory()->create();
            $employee = Empleado::create(['user_id' => $user->id, 'nombre_completo' => $name, 'numero_empleado' => 'E'.$index]);
            $state = TipoEstado::create(['nombre' => $name]);
            $type = TipoCargoFirma::create(['nombre' => $name]);
            $cargo = CargoFirma::create(['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $type->id, 'tipo_estado_id' => $state->id]);
            $stage = $flow->etapas()->create(['codigo' => 'C'.$index, 'nombre' => $name, 'orden' => $index + 1, 'cargo_firma_id' => $cargo->id,
                'usuario_responsable_id' => $user->id, 'requiere_asignacion' => true, 'aplica_inscripcion' => true, 'activo' => true]);
            $legacy->push($project->firma_proyecto()->create(['empleado_id' => $employee->id, 'cargo_firma_id' => $cargo->id,
                'estado_revision' => $subsanacion && $index === 1 ? 'Rechazado' : 'Pendiente', 'hash' => 'legacy-'.$index]));
            $stages->push($stage);
            $users->push($user);
        }
        $stateId = $subsanacion ? TipoEstado::create(['nombre' => 'Subsanacion'])->id : $stages[1]->cargoFirma->tipo_estado_id;
        EstadoProyecto::withoutEvents(fn () => $project->estado_proyecto()->create(['empleado_id' => $users[0]->empleado->id,
            'tipo_estado_id' => $stateId, 'fecha' => now(), 'comentario' => 'Origen', 'es_actual' => true]));

        return [$project, $flow, $stages, $users, $legacy];
    }

    public function test_pendiente_anterior_se_adopta_primero_y_solo_el_recibe_notificacion(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $diagnosis = $service->diagnosticar($project, $flow);
        $this->assertSame([], $diagnosis['bloqueos']);
        $this->assertSame($stages[1]->id, $diagnosis['etapa_sugerida_id']);
        $this->assertSame($stages[0]->id, $diagnosis['etapa_inicio_id']);
        $adoption = $service->adoptar($project, $flow, 'EN_REVISION', $stages[0]->id, [], $users[0]);
        $this->assertSame([], $adoption->evidencia['etapas_anteriores']);
        $this->assertCount(3, $adoption->evidencia['nuevo_recorrido']);
        $this->assertSame('Enlace Vinculacion', $adoption->estado_origen);
        $signatures = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertTrue($project->fresh()->firmaEsActualEnFlujoPorEtapa($signatures[0]));
        $this->assertFalse($project->fresh()->firmaEsActualEnFlujoPorEtapa($signatures[1]));
        Mail::assertQueuedCount(1);
        Mail::assertQueued(EtapaFlujoPendiente::class, fn ($mail) => $mail->hasTo($users[0]->email));
    }

    public function test_aprobacion_posterior_se_conserva_sin_repetir_ni_alterar_firma(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        $legacy[2]->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);
        $before = $legacy[2]->fresh()->toArray();
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[0]->id, [], $users[0]);
        $this->assertSame($before, $legacy[2]->fresh()->toArray());
        $this->assertSame([$stages[2]->id], array_column($adoption->evidencia['etapas_anteriores'], 'id'));
        $this->assertSame([$stages[0]->id, $stages[1]->id], $project->firmasDeEtapasDelFlujo($flow->id)->pluck('flujo_aprobacion_etapa_id')->all());
    }

    public function test_aprobacion_eliminada_no_excluye_una_etapa(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        DB::table('firma_proyecto')->where('id', $legacy[0]->id)->update(['estado_revision' => 'Aprobado', 'deleted_at' => now()]);
        $diagnosis = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($project, $flow);
        $this->assertSame($stages[0]->id, $diagnosis['etapa_inicio_id']);
        $this->assertSame('Sin evidencia', $diagnosis['etapas'][0]['evidencia_legacy']['estado']);
        $this->assertFalse($diagnosis['etapas'][0]['es_anterior']);
    }

    public function test_editar_catalogo_no_cambia_nombre_ni_orden_de_aprobacion_historica(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        $legacy[2]->update(['estado_revision' => 'Aprobado']);
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[0]->id, [], $users[0]);
        $stages[2]->update(['orden' => 99, 'nombre' => 'Nombre posterior']);
        $stage = $project->fresh()->etapasDelExpediente()->firstWhere('id', $stages[2]->id);
        $this->assertSame('Director centro', $stage->nombre);
        $this->assertSame(3, $stage->orden);
    }

    public function test_cargo_compartido_se_distingue_por_el_rol_historico(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        foreach ([1 => 'Enlace Vinculacion', 2 => 'Director Vinculacion'] as $index => $roleName) {
            $role = \Spatie\Permission\Models\Role::create(['name' => $roleName, 'guard_name' => 'web']);
            $users[$index]->assignRole($role);
            $stages[$index]->update(['cargo_firma_id' => $stages[1]->cargo_firma_id, 'rol_revisor_id' => $role->id]);
            $legacy[$index]->update(['cargo_firma_id' => $stages[1]->cargo_firma_id, 'rol_requerido' => $roleName,
                'estado_revision' => $index === 2 ? 'Aprobado' : 'Pendiente']);
        }
        $diagnosis = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($project, $flow->fresh());
        $this->assertSame([], $diagnosis['bloqueos']);
        $this->assertSame($stages[1]->id, $diagnosis['etapa_sugerida_id']);
        $this->assertSame($stages[0]->id, $diagnosis['etapa_inicio_id']);
        $this->assertTrue($diagnosis['etapas'][2]['es_anterior']);
        $this->assertFalse($diagnosis['etapas'][1]['es_anterior']);
    }

    private function contextoSubsanacionConEstadoAnterior(string $nombre): array
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context(true);
        $legacy[1]->update(['estado_revision' => 'Pendiente']);
        $state = TipoEstado::create(['nombre' => $nombre]);
        foreach ([1 => 'Revisor Vinculacion', 2 => 'Director Vinculacion'] as $index => $roleName) {
            $role = \Spatie\Permission\Models\Role::create(['name' => $roleName, 'guard_name' => 'web']);
            $users[$index]->assignRole($role);
            $stages[$index]->update(['cargo_firma_id' => $stages[1]->cargo_firma_id, 'rol_revisor_id' => $role->id]);
            $legacy[$index]->update(['cargo_firma_id' => $stages[1]->cargo_firma_id, 'rol_requerido' => $roleName]);
        }
        $project->estado_proyecto()->update(['es_actual' => false]);
        foreach ([$state->id, TipoEstado::where('nombre', 'Subsanacion')->value('id')] as $i => $stateId) {
            EstadoProyecto::withoutEvents(fn () => $project->estado_proyecto()->create([
                'empleado_id' => $users[0]->empleado->id, 'tipo_estado_id' => $stateId,
                'fecha' => now(), 'comentario' => 'Historial de devolución', 'es_actual' => $i === 1,
            ]));
        }

        return [$project->fresh(), $flow->fresh(), $stages, $users, $legacy];
    }

    public function test_subsanacion_interpreta_en_revision_por_rol_y_conserva_aprobaciones(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->contextoSubsanacionConEstadoAnterior('En revision');
        $legacy[0]->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);
        $before = $legacy[0]->fresh()->toArray();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $preview = $service->diagnosticar($project, $flow);
        $this->assertSame([], $preview['bloqueos']);
        $this->assertSame($stages[1]->id, $preview['etapa_inicio_id']);
        $this->assertSame([$stages[1]->id, $stages[2]->id], $preview['recorrido_ids']);
        $this->assertStringContainsString('En revision', $preview['razon_etapa']);
        $adoption = $service->adoptar($project, $flow, 'SUBSANACION', $stages[1]->id, [], $users[0], 'Motivo original');
        $this->assertSame($before, $legacy[0]->fresh()->toArray());
        $this->assertSame([$stages[0]->id], array_column($adoption->evidencia['etapas_anteriores'], 'id'));
        $this->assertSame('Subsanacion', $project->fresh()->estado->tipoestado->nombre);
        Mail::assertNothingQueued();
    }

    public function test_subsanacion_en_revision_final_prioriza_director_y_no_omite_pendientes(): void
    {
        [$project, $flow, $stages, $users] = $this->contextoSubsanacionConEstadoAnterior('En revision final');
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $preview = $service->diagnosticar($project, $flow);
        $this->assertSame([], $preview['bloqueos']);
        $this->assertSame($stages[2]->id, $preview['etapa_inicio_id']);
        $this->assertSame([$stages[2]->id, $stages[0]->id, $stages[1]->id], $preview['recorrido_ids']);
        $adoption = $service->adoptar($project, $flow, 'SUBSANACION', $stages[2]->id, [], $users[0], 'Motivo original');
        $this->assertSame([], $adoption->evidencia['etapas_anteriores']);
        $signatures = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertSame($preview['recorrido_ids'], $signatures->pluck('flujo_aprobacion_etapa_id')->all());
        $this->assertSame(['Rechazado', 'Pendiente', 'Pendiente'], $signatures->pluck('estado_revision')->all());
    }

    public function test_subsanacion_bloquea_estado_previo_que_contradice_aprobacion_vigente(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->contextoSubsanacionConEstadoAnterior('En revision');
        $legacy[1]->update(['estado_revision' => 'Aprobado']);
        $preview = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($project, $flow);
        $this->assertStringContainsString('repetiría una aprobación', implode(' ', $preview['bloqueos']));
        $this->assertNotContains($stages[1]->id, $preview['recorrido_ids']);
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
    }

    public function test_subsanacion_no_busca_un_estado_mas_antiguo_si_falta_el_rol_de_retorno(): void
    {
        [$project, $flow, $stages] = $this->contextoSubsanacionConEstadoAnterior('En revision final');
        $stages[2]->update(['activo' => false]);
        $preview = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar($project, $flow->fresh());
        $this->assertNull($preview['etapa_inicio_id']);
        $this->assertNotEmpty($preview['bloqueos']);
        $this->assertStringContainsString('En revision final', $preview['razon_etapa']);
    }

    private function asignarRolFirmante($stage, User $user): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $stage->nombre, 'guard_name' => 'web']);
        $stage->update(['rol_revisor_id' => $role->id]);
        $user->assignRole($role);
    }

    private function firmaGuardada(User $user): \App\Models\Personal\FirmaSelloEmpleado
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $path = 'firma-test-'.$user->id.'.png';
        $image = imagecreatetruecolor(16, 16);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imageline($image, 1, 14, 14, 1, imagecolorallocate($image, 0, 0, 0));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $bytes);

        return \App\Models\Personal\FirmaSelloEmpleado::create(['empleado_id' => $user->empleado->id, 'tipo' => 'firma', 'estado' => true, 'ruta_storage' => $path]);
    }

    public function test_desde_cero_dos_firmas_historicas_reenvio_y_bandejas_de_cinco_etapas(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context(true);
        // El sistema anterior perdió todas las aprobaciones durante la subsanación.
        $legacy[1]->update(['estado_revision' => 'Pendiente']);
        $state = TipoEstado::create(['nombre' => 'En revision']);
        $type = TipoCargoFirma::create(['nombre' => 'Revisor Vinculacion']);
        $cargo = CargoFirma::create(['descripcion' => 'DVUS', 'tipo_cargo_firma_id' => $type->id, 'tipo_estado_id' => $state->id]);
        foreach (['Revisor Vinculacion', 'Director Vinculacion'] as $name) {
            $user = User::factory()->create();
            Empleado::create(['user_id' => $user->id, 'nombre_completo' => $name, 'numero_empleado' => 'E'.$user->id]);
            $stage = $flow->etapas()->create(['codigo' => 'C'.$users->count(), 'nombre' => $name,
                'orden' => $users->count() + 1, 'cargo_firma_id' => $cargo->id,
                'tipo_etapa' => 'REVISION', 'usuario_responsable_id' => $user->id,
                'requiere_asignacion' => true, 'aplica_inscripcion' => true, 'activo' => true]);
            $stages->push($stage);
            $users->push($user);
        }
        foreach ($stages as $index => $stage) {
            $this->asignarRolFirmante($stage, $users[$index]);
            $users[$index]->forceFill(['active_role_id' => $stage->fresh()->rol_revisor_id])->save();
        }
        $this->autorizarAdaptacion($users[0]);
        $first = $this->firmaGuardada($users[0]);
        $bytes = \Illuminate\Support\Facades\Storage::disk('public')->get($first->ruta_storage);
        $second = $this->firmaGuardada($users[1]);
        \Illuminate\Support\Facades\Storage::disk('public')->put($first->ruta_storage, $bytes);
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $project, $flow, 'SUBSANACION', $stages[2]->id, [], $users[0], null, '', [
                $stages[0]->id => ['empleado_id' => $users[0]->empleado->id],
                $stages[1]->id => ['empleado_id' => $users[1]->empleado->id],
            ]
        );
        $cycle = $project->firmasDeEtapasDelFlujo($flow->id)->sortBy('orden_revision')->values();
        $this->assertSame(['Aprobado', 'Aprobado', 'Rechazado', 'Pendiente', 'Pendiente'], $cycle->pluck('estado_revision')->all());
        $this->assertCount(2, $adoption->evidencia['firmas_regularizadas']);
        $inbox = new class
        {
            use \App\Concerns\ResolvesFirmasPendientes;

            public function ids(): array
            {
                return $this->firmasDisponiblesQuery()->pluck('firma_proyecto.id')->all();
            }
        };
        foreach ($users as $user) {
            $this->actingAs($user->fresh());
            $this->assertSame([], $inbox->ids());
        }
        Mail::assertNothingQueued();
        \Illuminate\Support\Facades\Gate::define('docente.crear-proyecto', fn ($user) => true);
        \App\Models\Personal\EmpleadoProyecto::create(['proyecto_id' => $project->id, 'empleado_id' => $users[0]->empleado->id, 'rol' => 'Coordinador']);
        $sender = new class
        {
            use \App\Concerns\ReenviaDesdeSubsanacionPorEtapa;

            public function send($signature, $user)
            {
                return $this->reenviarDesdeSubsanacionPorEtapa($signature, $user, $this->empleadosPorEtapaParaReenvio($signature));
            }
        };
        $resumed = $sender->send($cycle[2], $users[0]);
        $this->assertSame($stages->slice(2)->pluck('id')->values()->all(), $resumed->pluck('flujo_aprobacion_etapa_id')->all());
        $this->assertSame('En revision', $project->fresh()->estado->tipoestado->nombre);
        Mail::assertQueuedCount(1);
        Mail::assertQueued(EtapaFlujoPendiente::class, fn ($mail) => $mail->hasTo($users[2]->email));
        // Simular las decisiones aprobadas y comprobar la bandeja real en cada avance.
        foreach ($resumed as $current) {
            foreach ($users as $user) {
                $this->actingAs($user->fresh());
                $expected = $user->id === $current->responsable_usuario_id ? [$current->id] : [];
                $this->assertSame($expected, $inbox->ids());
            }
            $current->update(['estado_revision' => 'Aprobado']);
        }
        foreach ($users as $user) {
            $this->actingAs($user->fresh());
            $this->assertSame([], $inbox->ids());
        }
        $this->assertTrue(app(\App\Services\Proyecto\ProyectoWorkflowService::class)->inscripcionCompletada($project->fresh()));
        $this->assertSame($first->id, $cycle[0]->fresh()->firma_id);
        $this->assertSame($second->id, $cycle[1]->fresh()->firma_id);
        $this->assertSame('Aprobado', $cycle[0]->fresh()->estado_revision);
        $this->assertSame('Aprobado', $cycle[1]->fresh()->estado_revision);
    }

    public function test_admin_regulariza_firma_visible_en_pdf_y_no_repite_la_etapa(): void
    {
        [$project, $flow, $stages, $users] = $this->context(true);
        $this->autorizarAdaptacion($users[0]);
        $this->asignarRolFirmante($stages[0], $users[0]);
        $signature = $this->firmaGuardada($users[0]);
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $project, $flow, 'SUBSANACION', $stages[1]->id, [], $users[0], 'Motivo original',
            'Se incorpora aprobación previa verificada por administración.',
            [$stages[0]->id => ['empleado_id' => $users[0]->empleado->id, 'fecha' => '2025-01-02']]
        );
        $cycle = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertSame(['Aprobado', 'Rechazado', 'Pendiente'], $cycle->pluck('estado_revision')->all());
        $this->assertSame($signature->id, $cycle[0]->firma_id);
        $this->assertSame($users[0]->id, $adoption->evidencia['firmas_regularizadas'][0]['registrada_por']);
        $this->assertSame('2025-01-02', $adoption->evidencia['firmas_regularizadas'][0]['fecha_historica']);
        Mail::assertNothingQueued();
        $html = view('components.fichas.firmas-fijas-proyecto', ['proyecto' => $project->fresh(), 'isPdf' => true])->render();
        $this->assertStringContainsString('Firma histórica incorporada por administración', $html);
        $this->assertStringContainsString('firma-test-'.$users[0]->id.'.png', $html);
        $this->assertStringNotContainsString('Firmado digitalmente', $html);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        $resumed = $project->fresh()->crearNuevoCicloDesdeFirmaRechazada($cycle[1], [
            $stages[1]->id => $users[1]->empleado->id, $stages[2]->id => $users[2]->empleado->id,
        ]);
        $this->assertSame([$stages[1]->id, $stages[2]->id], $resumed->pluck('flujo_aprobacion_etapa_id')->all());
        $this->assertSame('Aprobado', $cycle[0]->fresh()->estado_revision);
    }

    public function test_regularizacion_con_fecha_desconocida_conserva_la_firma_elegida_aunque_cambie_el_perfil(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $this->autorizarAdaptacion($users[0]);
        $this->asignarRolFirmante($stages[0], $users[1]);
        $signature = $this->firmaGuardada($users[1]);
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $project, $flow, 'EN_REVISION', $stages[2]->id, [], $users[0], null,
            'Se verificó el firmante histórico, distinto al responsable actual.',
            [$stages[0]->id => ['empleado_id' => $users[1]->empleado->id]]
        );
        $registered = $project->firmasDeEtapasDelFlujo($flow->id)->first();
        $this->assertSame($users[1]->empleado->id, $registered->empleado_id);
        $this->assertNull($registered->fecha_firma);
        $this->assertNull($adoption->evidencia['firmas_regularizadas'][0]['fecha_historica']);
        $signature->delete();
        \App\Models\Personal\FirmaSelloEmpleado::create(['empleado_id' => $users[1]->empleado->id, 'tipo' => 'firma', 'estado' => true, 'ruta_storage' => 'firma-posterior.png']);
        $html = view('components.fichas.firmas-fijas-proyecto', ['proyecto' => $project->fresh(), 'isPdf' => true])->render();
        $this->assertStringContainsString('firma-test-'.$users[1]->id.'.png', $html);
        $this->assertStringNotContainsString('firma-posterior.png', $html);
        $this->assertStringContainsString('Firma histórica incorporada por administración', $html);
    }

    public function test_selector_historico_filtra_por_rol_y_servidor_rechaza_otro_firmante(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $this->autorizarAdaptacion($users[0]);
        $this->asignarRolFirmante($stages[0], $users[0]);
        $this->asignarRolFirmante($stages[1], $users[1]);
        $this->firmaGuardada($users[0]);
        $this->firmaGuardada($users[1]);
        $this->actingAs($users[0]);
        $component = new \App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;
        $component->flowModal = true;
        $component->flowIsLegacyAdoption = true;
        $component->flowProyectoId = $project->id;
        $component->flowSelectedId = $flow->id;
        $component->flowManualStage = true;
        $component->flowStartStageId = $stages[2]->id;
        $component->updatedFlowStartStageId();
        $this->assertSame([$users[0]->empleado->id], array_column($component->flowHistoricalCandidates[$stages[0]->id], 'id'));
        $this->assertSame([$users[1]->empleado->id], array_column($component->flowHistoricalCandidates[$stages[1]->id], 'id'));
        $this->assertSame([], $component->flowHistoricalCandidates[$stages[2]->id]);
        $html = view('components.forms.searchable-user-select', [
            'model' => 'flowHistoricalSigners.'.$stages[0]->id.'.empleado_id',
            'options' => $component->flowHistoricalCandidates[$stages[0]->id],
        ])->render();
        $this->assertStringContainsString('type="search"', $html);
        $this->assertStringContainsString('filteredOptions', $html);
        $this->assertStringContainsString($users[0]->empleado->nombre_completo, $html);
        $this->assertStringNotContainsString($users[1]->empleado->nombre_completo, $html);
        try {
            app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow->fresh(), 'EN_REVISION', $stages[2]->id, [], $users[0], null,
                'Justificación administrativa.', [$stages[0]->id => ['empleado_id' => $users[1]->empleado->id]]);
            $this->fail('No debe aceptar firmantes de otro rol.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('rol configurado', $e->getMessage());
        }
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
    }

    public function test_director_enlace_no_puede_incorporar_firmas_aunque_pueda_adaptar(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'Director/Enlace', 'guard_name' => 'web']);
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $users[0]->assignRole($role);
        try {
            app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[2]->id, [], $users[0], null,
                'Justificación administrativa.', [$stages[0]->id => ['empleado_id' => $users[0]->empleado->id]]);
            $this->fail('Debe exigir rol admin.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Solo un administrador', $e->getMessage());
        }
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
        $this->assertCount(0, $project->firmasDeEtapasDelFlujo($flow->id));
    }

    public function test_regularizacion_rechaza_etapa_de_retorno_firmante_sin_firma_y_fecha_futura(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $this->autorizarAdaptacion($users[0]);
        $this->asignarRolFirmante($stages[0], $users[0]);
        $before = $project->firma_proyecto()->get()->toArray();
        foreach ([
            [$stages[2]->id => ['empleado_id' => $users[0]->empleado->id]],
            [$stages[0]->id => ['empleado_id' => 999999]],
            [$stages[0]->id => ['empleado_id' => $users[0]->empleado->id]],
        ] as $data) {
            try {
                app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[2]->id, [], $users[0], null, 'Justificación administrativa.', $data);
                $this->fail('No debe incorporar datos inválidos.');
            } catch (\RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
            $this->assertSame($before, $project->firma_proyecto()->get()->toArray());
        }
        $this->firmaGuardada($users[0]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('futuro');
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[2]->id, [], $users[0], null, 'Justificación administrativa.',
            [$stages[0]->id => ['empleado_id' => $users[0]->empleado->id, 'fecha' => now()->addDay()->toDateString()]]);
    }

    private function autorizarAdaptacion(User $user): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
    }

    public function test_seleccion_manual_prioriza_etapa_sin_aprobar_las_anteriores(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $this->autorizarAdaptacion($users[0]);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $preview = $service->diagnosticar($project, $flow, $stages[2]->id);
        $this->assertSame([$stages[2]->id, $stages[0]->id, $stages[1]->id], $preview['recorrido_ids']);
        $adoption = $service->adoptar($project, $flow, 'EN_REVISION', $stages[2]->id, [], $users[0], null, 'Decisión administrativa documentada.');
        $signatures = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertSame($preview['recorrido_ids'], $signatures->pluck('flujo_aprobacion_etapa_id')->all());
        $this->assertSame(['Pendiente', 'Pendiente', 'Pendiente'], $signatures->pluck('estado_revision')->all());
        $this->assertSame([], $adoption->evidencia['etapas_anteriores']);
        $this->assertSame($stages[0]->id, $adoption->evidencia['seleccion_etapa']['sugerida_id']);
        $this->assertSame('Decisión administrativa documentada.', $adoption->evidencia['seleccion_etapa']['motivo']);
        Mail::assertQueuedCount(1);
        Mail::assertQueued(EtapaFlujoPendiente::class, fn ($mail) => $mail->hasTo($users[2]->email));
    }

    public function test_subsanacion_sin_etapa_detectable_admite_decision_manual_y_reenvia_a_elegida(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context(true);
        $this->autorizarAdaptacion($users[0]);
        $legacy[1]->update(['estado_revision' => 'Pendiente']);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $this->assertNull($service->diagnosticar($project, $flow)['etapa_inicio_id']);
        $service->adoptar($project, $flow, 'SUBSANACION', $stages[2]->id, [], $users[0], 'Motivo original', 'Se revisó el expediente y se asigna retorno al director.');
        $this->assertSame('Subsanacion', $project->fresh()->estado->tipoestado->nombre);
        Mail::assertNothingQueued();
        $signatures = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertSame($stages[2]->id, $signatures->first()->flujo_aprobacion_etapa_id);
        $this->assertSame(['Rechazado', 'Pendiente', 'Pendiente'], $signatures->pluck('estado_revision')->all());
        $assignments = $stages->mapWithKeys(fn ($stage, $i) => [$stage->id => $users[$i]->empleado->id])->all();
        $resumed = $project->fresh()->crearNuevoCicloDesdeFirmaRechazada($signatures->first(), $assignments);
        $this->assertSame($stages[2]->id, $resumed->first()->flujo_aprobacion_etapa_id);
        $this->assertCount(3, $resumed);
    }

    public function test_adapta_subsanacion_manual_sin_pedir_motivos(): void
    {
        [$project, $flow, $stages, $users] = $this->context(true);
        $this->autorizarAdaptacion($users[0]);
        $project->estado_proyecto()->where('es_actual', true)->update(['comentario' => '']);
        $project->unsetRelation('estado');
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $project, $flow, 'SUBSANACION', $stages[1]->id, [], $users[0], null, ''
        );
        $this->assertSame('', $adoption->evidencia['motivo_subsanacion']);
        $this->assertSame('', $adoption->evidencia['seleccion_etapa']['motivo']);
        $this->assertSame('Subsanacion', $project->fresh()->estado->tipoestado->nombre);
        Mail::assertNothingQueued();
    }

    public function test_adaptacion_recupera_motivo_historico_sin_solicitarlo(): void
    {
        [$project, $flow, $stages, $users] = $this->context(true);
        $this->autorizarAdaptacion($users[0]);
        $project->estado_proyecto()->where('es_actual', true)->update(['comentario' => 'Corregir presupuesto.']);
        $project->unsetRelation('estado');
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
            $project, $flow, 'SUBSANACION', $stages[1]->id, [], $users[0], null, ''
        );
        $this->assertSame('Corregir presupuesto.', $adoption->evidencia['motivo_subsanacion']);
        $this->assertSame('Corregir presupuesto.', $project->fresh()->estado->comentario);
        Mail::assertNothingQueued();
    }

    public function test_seleccion_manual_exige_permiso_y_etapa_valida(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        foreach ([['Decisión administrativa documentada.', $stages[2]->id, false], ['Decisión administrativa documentada.', 99999, true]] as [$reason, $id, $authorized]) {
            if ($authorized) {
                $this->autorizarAdaptacion($users[0]);
            }
            try {
                $service->adoptar($project, $flow, 'EN_REVISION', $id, [], $users[0], null, $reason);
                $this->fail('Debe rechazar la selección inválida.');
            } catch (\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
            $this->assertFalse($project->adopcionFlujoLegacy()->exists());
            $this->assertCount(0, $project->firmasDeEtapasDelFlujo($flow->id));
        }
        Mail::assertNothingQueued();
    }

    public function test_componente_conserva_la_seleccion_manual_al_actualizar_diagnostico(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $this->autorizarAdaptacion($users[0]);
        $this->actingAs($users[0]);
        $component = new \App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;
        $component->flowModal = true;
        $component->flowIsLegacyAdoption = true;
        $component->flowProyectoId = $project->id;
        $component->flowSelectedId = $flow->id;
        $component->flowManualStage = true;
        $component->updatedFlowManualStage();
        $this->assertNull($component->flowStartStageId);
        $this->assertNull($component->flowDiagnosis['etapa_inicio_id']);
        $this->assertSame([], $component->flowDiagnosis['recorrido_ids']);
        $this->assertSame([], $component->flowReviewers);
        $this->assertStringContainsString('Seleccione la etapa', implode(' ', $component->flowDiagnosis['bloqueos']));
        $component->refreshFlowReviewerCandidates();
        $this->assertNull($component->flowStartStageId);
        $this->assertSame([], $component->flowReviewers);
        $component->flowStartStageId = $stages[2]->id;
        $component->updatedFlowStartStageId();
        $this->assertSame($stages[2]->id, $component->flowStartStageId);
        $this->assertTrue($component->flowDiagnosis['seleccion_manual']);
        $this->assertSame($stages[2]->id, $component->flowDiagnosis['recorrido_ids'][0]);
        $component->flowStartStageId = null;
        $component->updatedFlowStartStageId();
        $this->assertNull($component->flowStartStageId);
        $this->assertSame([], $component->flowDiagnosis['recorrido_ids']);
        $component->flowManualStage = false;
        $component->updatedFlowManualStage();
        $this->assertSame($stages[0]->id, $component->flowStartStageId);
        $component->updatedFlowSelectedId();
        $this->assertFalse($component->flowManualStage);
        $this->assertSame('', $component->flowManualStageReason);
    }

    public function test_adaptacion_manual_sin_elegir_etapa_no_guarda_ni_notifica(): void
    {
        [$project, $flow, $stages, $users] = $this->context(true);
        $this->autorizarAdaptacion($users[0]);
        $before = $project->firma_proyecto()->get()->toArray();
        try {
            app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar(
                $project, $flow, 'SUBSANACION', null, [], $users[0], 'Motivo original', 'Decisión administrativa documentada.'
            );
            $this->fail('Debe exigir una elección explícita.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Seleccione una etapa', $exception->getMessage());
        }
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
        $this->assertSame($before, $project->firma_proyecto()->get()->toArray());
        Mail::assertNothingQueued();
    }

    public function test_firma_coordinador_no_bloquea_ni_se_anula_al_adaptar(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $type = TipoCargoFirma::create(['nombre' => 'Coordinador Proyecto']);
        $cargo = CargoFirma::create(['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $type->id]);
        $signature = $project->firma_proyecto()->create(['empleado_id' => $users[0]->empleado->id, 'cargo_firma_id' => $cargo->id, 'estado_revision' => 'Pendiente']);
        $before = $signature->fresh()->toArray();
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[0]->id, [], $users[0]);
        $this->assertSame($before, $signature->fresh()->toArray());
    }

    public function test_historial_ambiguo_bloquea_sin_modificar_firmas(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        $stages[2]->update(['cargo_firma_id' => $stages[0]->cargo_firma_id]);
        $before = $project->firma_proyecto()->get()->toArray();
        try {
            app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'EN_REVISION', $stages[0]->id, [], $users[0]);
            $this->fail('No debe adaptar correspondencias ambiguas.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('varias etapas', $exception->getMessage());
        }
        $this->assertSame($before, $project->firma_proyecto()->get()->toArray());
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
        Mail::assertNothingQueued();
    }

    public function test_responsable_faltante_bloquea_pero_eleccion_del_emisor_permite_adoptar(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        $stages[0]->update(['usuario_responsable_id' => null]);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $this->assertStringContainsString('Configure el responsable fijo', implode(' ', $service->diagnosticar($project, $flow)['bloqueos']));
        $stages[0]->update(['emisor_define_destinatario' => true]);
        $adoption = $service->adoptar($project, $flow->fresh(), 'EN_REVISION', $stages[0]->id, [$stages[0]->id => $users[0]->id], $users[0]);
        $this->assertSame($users[0]->id, $adoption->revisor_usuario_id);
    }

    public function test_estado_final_no_inventa_aprobaciones_ni_pierde_la_inscripcion(): void
    {
        [$project, $flow, $stages, $users] = $this->context();
        EstadoProyecto::withoutEvents(fn () => $project->estado->update(['tipo_estado_id' => TipoEstado::create(['nombre' => 'Registrado'])->id]));
        $project = $project->fresh();
        $adoption = app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'COMPLETADO', null, [], $users[0]);
        $this->assertSame([], $adoption->evidencia['etapas_anteriores']);
        $this->assertCount(0, $project->firmasDeEtapasDelFlujo($flow->id));
        $this->assertTrue(app(\App\Services\Proyecto\ProyectoWorkflowService::class)->inscripcionCompletada($project->fresh()));
        Mail::assertNothingQueued();
    }

    public function test_vista_previa_obsoleta_no_omite_una_revision_nueva(): void
    {
        [$project, $flow, $stages, $users, $legacy] = $this->context();
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);
        $legacy[0]->update(['estado_revision' => 'Aprobado']);
        $preview = $service->diagnosticar($project, $flow);
        $legacy[0]->update(['estado_revision' => 'Pendiente']);
        try {
            $service->adoptar($project, $flow, 'EN_REVISION', $preview['etapa_inicio_id'], [], $users[0]);
            $this->fail('Debe exigir actualizar la vista previa.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('no coincide', $exception->getMessage());
        }
        $this->assertFalse($project->adopcionFlujoLegacy()->exists());
        $this->assertSame('Pendiente', $legacy[0]->fresh()->estado_revision);
        Mail::assertNothingQueued();
    }

    public function test_subsanacion_conserva_rechazo_y_reenvia_desde_primera_pendiente(): void
    {
        [$project, $flow, $stages, $users] = $this->context(true);
        app(ProyectoLegacyWorkflowAdoptionService::class)->adoptar($project, $flow, 'SUBSANACION', $stages[1]->id, [], $users[0], 'Motivo original');
        $signatures = $project->firmasDeEtapasDelFlujo($flow->id);
        $this->assertSame(['Rechazado', 'Pendiente', 'Pendiente'], $signatures->pluck('estado_revision')->all());
        Mail::assertNothingQueued();
        \Illuminate\Support\Facades\Gate::define('docente.crear-proyecto', fn ($user) => true);
        \App\Models\Personal\EmpleadoProyecto::create(['proyecto_id' => $project->id, 'empleado_id' => $users[0]->empleado->id, 'rol' => 'Coordinador']);
        $sender = new class
        {
            use \App\Concerns\ReenviaDesdeSubsanacionPorEtapa;

            public function send($signature, $user)
            {
                return $this->reenviarDesdeSubsanacionPorEtapa($signature, $user, $this->empleadosPorEtapaParaReenvio($signature));
            }
        };
        $resumed = $sender->send($signatures[0], $users[0]);
        $this->assertSame([1, 2, 3], $resumed->pluck('orden_revision')->all());
        $this->assertSame(['Pendiente', 'Pendiente', 'Pendiente'], $resumed->pluck('estado_revision')->all());
        Mail::assertQueuedCount(1);
        Mail::assertQueued(EtapaFlujoPendiente::class, fn ($mail) => $mail->hasTo($users[1]->email));
    }
}
