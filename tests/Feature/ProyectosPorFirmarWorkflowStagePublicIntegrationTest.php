<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectosPorFirmarWorkflowStagePublicIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bandeja_muestra_solo_firma_por_etapa_actual_y_autorizada(): void
    {
        $context = $this->contexto(2);
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol bandeja etapa');
        $actual = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $posterior = $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);

        Livewire::actingAs($user)
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->assertSee($context['proyecto']->nombre_proyecto)
            ->assertSee($actual->cargo_firma->tipoCargoFirma->nombre);

        $ids = $this->firmasDisponiblesIds($user);

        $this->assertContains($actual->id, $ids);
        $this->assertNotContains($posterior->id, $ids);
    }

    public function test_bandeja_no_muestra_otro_empleado_rol_activo_incorrecto_o_rol_no_asignado(): void
    {
        $context = $this->contexto();
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol restricciones');
        [, $otroEmpleado] = $this->usuarioEmpleadoConRol($role->name, $role);
        $otroRol = $this->rol('Rol activo incorrecto');
        $firmaOtroEmpleado = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $otroEmpleado, ['rol_requerido' => $role->name]);

        $this->assertNotContains($firmaOtroEmpleado->id, $this->firmasDisponiblesIds($user));

        $user->assignRole($otroRol);
        $user->forceFill(['active_role_id' => $otroRol->id])->save();
        $firmaRolCorrecto = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'rol_requerido' => $role->name,
        ], revisionCiclo: 2);

        $this->assertNotContains($firmaRolCorrecto->id, $this->firmasDisponiblesIds($user->fresh()));

        [$sinRolAsignado, $empleadoSinRol, $roleSinAsignar] = $this->usuarioEmpleadoConRol('Rol no asignado publico', assignRole: false);
        $firmaSinRolAsignado = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleadoSinRol, [
            'rol_requerido' => $roleSinAsignar->name,
        ], revisionCiclo: 3);

        $this->assertNotContains($firmaSinRolAsignado->id, $this->firmasDisponiblesIds($sinRolAsignado));
    }

    public function test_bandeja_respeta_responsable_fijo_rechazo_previo_ciclo_y_legacy(): void
    {
        $context = $this->contexto(2);
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol responsable publico');
        [$otroUser] = $this->usuarioEmpleadoConRol($role->name, $role);
        $responsableOtro = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'responsable_usuario_id' => $otroUser->id,
            'rol_requerido' => $role->name,
        ]);

        $this->assertNotContains($responsableOtro->id, $this->firmasDisponiblesIds($user));

        $contextRechazado = $this->contexto(2);
        $rechazada = $this->firmaEtapa($contextRechazado['proyecto'], $contextRechazado['etapas'][0], $empleado, [
            'estado_revision' => 'Rechazado',
            'rol_requerido' => $role->name,
        ]);
        $bloqueada = $this->firmaEtapa($contextRechazado['proyecto'], $contextRechazado['etapas'][1], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertSame('Rechazado', $rechazada->estado_revision);
        $this->assertNotContains($bloqueada->id, $this->firmasDisponiblesIds($user));

        $legacy = $this->firmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $user->forceFill(['active_role_id' => null])->save();

        $this->assertContains($legacy->id, $this->firmasDisponiblesIds($user->fresh()));
    }

    public function test_bandeja_maneja_etapas_con_mismo_cargo_y_documento_por_etapa(): void
    {
        $context = $this->contexto(2, mismoCargo: true);
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol mismo cargo publico');
        $primera = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'estado_revision' => 'Aprobado',
            'rol_requerido' => $role->name,
        ]);
        $segunda = $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'rol_requerido' => $role->name,
        ]);
        $documento = $this->documentoConEstado($context['proyecto'], $context['estados'][0]->id);
        $firmaDocumento = $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'rol_requerido' => $role->name,
        ], $documento, 2);

        $ids = $this->firmasDisponiblesIds($user);

        $this->assertNotContains($primera->id, $ids);
        $this->assertContains($segunda->id, $ids);
        $this->assertContains($firmaDocumento->id, $ids);
        $this->assertSame($primera->cargo_firma_id, $segunda->cargo_firma_id);
    }

    public function test_can_act_on_firma_delega_por_etapa_y_conserva_legacy(): void
    {
        $context = $this->contexto();
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol delegacion publica');
        $firmaEtapa = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $legacy = $this->firmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);

        $this->actingAs($user);
        $this->assertTrue($this->canActOnFirma($firmaEtapa));

        $user->forceFill(['active_role_id' => null])->save();
        $this->actingAs($user->fresh());
        $this->assertTrue($this->canActOnFirma($legacy));
    }

    public function test_authorize_firma_action_bloquea_etapa_no_autorizada_sin_fallback_legacy(): void
    {
        $context = $this->contexto();
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol autorizado publico');
        $otroRol = $this->rol('Rol no autorizado publico');
        $user->assignRole($otroRol);
        $user->forceFill(['active_role_id' => $otroRol->id])->save();
        $firma = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        Livewire::actingAs($user->fresh())
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->call('openRechazar', $firma->id)
            ->assertForbidden();
    }

    public function test_aprobar_publico_usa_flujo_por_etapa_y_no_legacy(): void
    {
        $context = $this->contexto(2, mismoCargo: true);
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol aprobar publico');
        $firma = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $siguienteMismoCargo = $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);

        Livewire::actingAs($user)
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->call('aprobar', $firma->id)
            ->assertOk();

        $this->assertSame('Aprobado', $firma->refresh()->estado_revision);
        $this->assertSame('Pendiente', $siguienteMismoCargo->refresh()->estado_revision);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($siguienteMismoCargo));
        $this->assertSame($context['estados'][0]->id, $context['proyecto']->estado->tipo_estado_id);
    }

    public function test_aprobar_publico_conserva_legacy(): void
    {
        $context = $this->contexto();
        $this->tipoEstado('En curso');
        [$user, $empleado] = $this->usuarioEmpleadoConRol('Rol aprobar legacy');
        $legacy = $this->firmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $user->forceFill(['active_role_id' => null])->save();

        Livewire::actingAs($user->fresh())
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->call('aprobar', $legacy->id)
            ->assertOk();

        $this->assertSame('Aprobado', $legacy->refresh()->estado_revision);
        $this->assertNull($legacy->flujo_aprobacion_etapa_id);
    }

    public function test_rechazar_publico_usa_flujo_por_etapa_bloquea_posterior_y_no_legacy(): void
    {
        $context = $this->contexto(2, mismoCargo: true);
        $this->tipoEstado('Subsanacion');
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol rechazar publico');
        $firma = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $posteriorMismoCargo = $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);

        Livewire::actingAs($user)
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->set('rechazarId', $firma->id)
            ->set('rechazarComentario', 'Debe corregir observaciones')
            ->call('rechazar')
            ->assertOk();

        $this->assertSame('Rechazado', $firma->refresh()->estado_revision);
        $this->assertNull($firma->firma_id);
        $this->assertNull($firma->sello_id);
        $this->assertSame('Pendiente', $posteriorMismoCargo->refresh()->estado_revision);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($posteriorMismoCargo));
        $this->assertSame('Subsanacion', $context['proyecto']->estado->tipoestado->nombre);
        $this->assertSame('Debe corregir observaciones', $context['proyecto']->estado->comentario);
    }

    public function test_rechazar_publico_requiere_comentario_y_conserva_legacy(): void
    {
        $context = $this->contexto();
        [$user, $empleado] = $this->usuarioEmpleadoConRol('Rol rechazar legacy');
        $legacy = $this->firmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $user->forceFill(['active_role_id' => null])->save();

        Livewire::actingAs($user->fresh())
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->set('rechazarId', $legacy->id)
            ->set('rechazarComentario', '')
            ->call('rechazar')
            ->assertHasErrors(['rechazarComentario']);

        $this->assertSame('Pendiente', $legacy->refresh()->estado_revision);
    }

    public function test_aprobacion_por_etapa_revierte_si_falla_y_no_crea_firmas_nuevas(): void
    {
        $context = $this->contexto(2);
        [$user, $empleado, $role] = $this->usuarioEmpleadoConRol('Rol rollback publico');
        $firma = $this->firmaEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $this->firmaEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'estado_revision' => 'Rechazado',
            'rol_requerido' => $role->name,
        ]);
        $firmasAntes = FirmaProyecto::count();

        try {
            Livewire::actingAs($user)
                ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
                ->call('aprobar', $firma->id);

            $this->fail('La aprobacion por etapa debio fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Pendiente', $firma->refresh()->estado_revision);
            $this->assertSame($firmasAntes, FirmaProyecto::count());
        }
    }

    private function firmasDisponiblesIds(User $user): array
    {
        $this->actingAs($user);
        $component = new ProyectosPorFirmar;
        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'firmasDisponiblesQuery');
        $method->setAccessible(true);

        return $method->invoke($component)->pluck('firma_proyecto.id')->all();
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'canActOnFirma');
        $method->setAccessible(true);

        return $method->invoke(new ProyectosPorFirmar, $firma);
    }

    private function contexto(int $etapas = 1, bool $mismoCargo = false): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto publico '.uniqid(),
            'codigo_proyecto' => 'PUB-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_PUB_'.uniqid(),
            'nombre' => 'Flujo publico',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $empleado = $this->empleado();
        $estados = [];
        $cargos = [];
        $etapasFlujo = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $etapas; $orden++) {
            $estados[] = $this->tipoEstado('Estado publico '.$orden.' '.uniqid());
            $cargoCompartido = $mismoCargo && $cargoCompartido
                ? $cargoCompartido
                : $this->cargoFirma($estados[$orden - 1]->id, 'Cargo publico '.$orden.' '.uniqid());
            $cargos[] = $cargoCompartido;
            $etapasFlujo[] = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'PUB_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa publica '.$orden,
                'cargo_firma_id' => $cargoCompartido->id,
                'activo' => true,
            ]);
        }

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $estados[0]->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return [
            'proyecto' => $proyecto,
            'flujo' => $flujo,
            'etapas' => $etapasFlujo,
            'estados' => $estados,
            'cargos' => $cargos,
        ];
    }

    private function firmaEtapa(
        Proyecto $proyecto,
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = [],
        ?DocumentoProyecto $documento = null,
        int $revisionCiclo = 1
    ): FirmaProyecto {
        $relation = $documento ? $documento->firma_documento() : $proyecto->firma_proyecto();

        return $relation->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-publico',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => null,
            'responsable_usuario_id' => null,
            'revision_ciclo' => $revisionCiclo,
        ], $attributes));
    }

    private function firmaLegacy(Proyecto $proyecto, CargoFirma $cargo, Empleado $empleado): FirmaProyecto
    {
        return $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-legacy-publico',
        ]);
    }

    private function usuarioEmpleadoConRol(string $nombreRol, ?Role $role = null, bool $assignRole = true): array
    {
        $role = $role ?: $this->rol($nombreRol);
        $user = User::create([
            'name' => 'Usuario publico',
            'email' => 'publico-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->empleado($user);

        if ($assignRole) {
            $user->assignRole($role);
        }

        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function empleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado publico',
            'email' => 'empleado-publico-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado publico',
            'numero_empleado' => 'PUB-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function rol(string $nombre): Role
    {
        return Role::create([
            'name' => $nombre.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function tipoEstado(string $nombre): TipoEstado
    {
        return TipoEstado::create(['nombre' => $nombre]);
    }

    private function cargoFirma(int $tipoEstadoId, string $nombre): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create(['nombre' => $nombre]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }

    private function documentoConEstado(Proyecto $proyecto, int $tipoEstadoId): DocumentoProyecto
    {
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Intermedio',
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $documento->estado_documento()->create([
            'empleado_id' => $this->empleado()->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return $documento;
    }
}
