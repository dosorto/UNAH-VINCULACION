<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectosPorFirmarWorkflowStageAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_asignado_con_empleado_y_rol_activo_correcto_puede_actuar(): void
    {
        [$proyecto, $flujo, $etapas, $cargo] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Revisor etapa');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user));
        $this->assertSame($cargo->id, $firma->cargo_firma_id);
        $this->assertSame($flujo->id, $firma->flujo_aprobacion_id);
    }

    public function test_solo_firmas_pendientes_actuales_y_no_eliminadas_son_autorizables(): void
    {
        foreach (['Aprobado', 'Rechazado', 'Anulado'] as $estado) {
            [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol '.$estado);
            $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
                'estado_revision' => $estado,
                'rol_requerido' => $role->name,
            ]);

            $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user));
        }

        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol eliminado');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);
        $firma->newQuery()->whereKey($firma->id)->update(['deleted_at' => now()]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma->refresh(), $user));
    }

    public function test_firma_incompleta_o_legacy_falla_de_forma_segura(): void
    {
        $casos = [
            'legacy' => [],
            'sin flujo' => ['flujo_aprobacion_id' => null],
            'sin etapa' => ['flujo_aprobacion_etapa_id' => null],
            'sin ciclo' => ['revision_ciclo' => null],
            'sin orden' => ['orden_revision' => null],
        ];

        foreach ($casos as $nombre => $attributes) {
            [$proyecto, , $etapas, $cargo] = $this->crearProyectoConFlujo();
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol '.$nombre);
            $firma = $nombre === 'legacy'
                ? $this->crearFirmaLegacy($proyecto, $cargo, $empleado, ['rol_requerido' => $role->name])
                : $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, array_merge([
                    'rol_requerido' => $role->name,
                ], $attributes));

            $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user), $nombre);
        }

        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol sin empleado en firma');
        $firmaSinEmpleado = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);
        $firmaSinEmpleado->forceFill(['empleado_id' => null]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaSinEmpleado, $user));
    }

    public function test_firma_posterior_depende_de_estados_previos(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol secuencia');
        $primera = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, ['rol_requerido' => $role->name]);
        $segunda = $this->crearFirmaDeEtapa($proyecto, $etapas[1], $empleado, ['rol_requerido' => $role->name]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($segunda, $user));

        $primera->update(['estado_revision' => 'Rechazado']);
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($segunda->refresh(), $user));

        $primera->update(['estado_revision' => 'Aprobado']);
        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($segunda->refresh(), $user));

        $primera->update(['estado_revision' => 'Anulado']);
        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($segunda->refresh(), $user));
    }

    public function test_empleado_asignado_y_rol_activo_son_obligatorios(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol exacto');
        [$otroUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        $otroRol = $this->crearRol('Otro rol activo');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $otroUser));

        $user->assignRole($otroRol);
        $user->forceFill(['active_role_id' => $otroRol->id])->save();
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user->fresh()));

        $user->forceFill(['active_role_id' => $role->id])->save();
        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user->fresh()));

        $user->forceFill(['active_role_id' => null])->save();
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user->fresh()));
    }

    public function test_active_role_id_debe_corresponder_a_un_rol_real_del_usuario(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol no asignado', assignRole: false);
        $user->forceFill(['active_role_id' => $role->id])->save();
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user));
    }

    public function test_usuario_sin_empleado_no_puede_actuar(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$userAsignado, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol sin empleado');
        $userSinEmpleado = $this->crearUsuario();
        $userSinEmpleado->assignRole($role);
        $userSinEmpleado->forceFill(['active_role_id' => $role->id])->save();
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $userAsignado));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $userSinEmpleado));
    }

    public function test_responsable_fijo_no_puede_ser_sustituido_y_respeta_rol_snapshot(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$responsable, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol responsable');
        [$otroUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        $otroRol = $this->crearRol('Rol responsable incorrecto');
        $responsable->assignRole($otroRol);
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'responsable_usuario_id' => $responsable->id,
            'rol_requerido' => $role->name,
        ]);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $responsable));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $otroUser));

        $responsable->forceFill(['active_role_id' => $otroRol->id])->save();
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $responsable->fresh()));
    }

    public function test_responsable_fijo_requiere_empleado_correcto_y_puede_actuar_sin_rol_requerido(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$responsable, $empleado] = $this->crearUsuarioEmpleadoConRol('Rol opcional');
        [$responsableOtroEmpleado] = $this->crearUsuarioEmpleadoConRol('Rol opcional otro');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'responsable_usuario_id' => $responsable->id,
            'rol_requerido' => null,
        ]);
        $firmaOtroEmpleado = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'responsable_usuario_id' => $responsableOtroEmpleado->id,
            'rol_requerido' => null,
        ], revisionCiclo: 2);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $responsable));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaOtroEmpleado, $responsableOtroEmpleado));
    }

    public function test_firma_sin_responsable_y_sin_rol_no_autoriza_y_admin_no_tiene_bypass(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado] = $this->crearUsuarioEmpleadoConRol('Rol cualquiera');
        [$admin] = $this->crearUsuarioEmpleadoConRol('admin');
        $firmaSinRol = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, ['rol_requerido' => null]);
        $firmaConRol = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => 'Rol inexistente',
        ], revisionCiclo: 2);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaSinRol, $user));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaConRol, $admin));
    }

    public function test_estado_actual_del_proyecto_debe_coincidir_con_cargo(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol estado proyecto');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, ['rol_requerido' => $role->name]);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user));

        $proyecto->estado_proyecto()->update(['es_actual' => false]);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $this->crearTipoEstado('Estado incompatible')->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma->refresh(), $user));
    }

    public function test_documento_con_estado_compatible_autoriza_sin_mezclar_otros_documentos(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol documento');
        $documentoA = $this->crearDocumentoConEstado($proyecto, $etapas[0]->cargoFirma->tipo_estado_id);
        $documentoB = $this->crearDocumentoConEstado($proyecto, $etapas[0]->cargoFirma->tipo_estado_id);
        $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ], $documentoA);
        $firmaDocumentoB = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ], $documentoB);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaDocumentoB, $user));

        $documentoB->estado_documento()->update(['es_actual' => false]);
        $documentoB->estado_documento()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $this->crearTipoEstado('Estado doc incompatible')->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaDocumentoB->refresh(), $user));
    }

    public function test_otros_firmables_proyectos_eliminados_documentos_eliminados_y_etapas_null_no_autorizan(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol datos historicos');
        $firmaFicha = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);
        $firmaFicha->forceFill([
            'firmable_type' => FichaActualizacion::class,
            'firmable_id' => 999999,
        ]);
        $firmaEtapaNull = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, ['rol_requerido' => $role->name], revisionCiclo: 2);
        $firmaEtapaNull->update(['flujo_aprobacion_etapa_id' => null]);

        $documento = $this->crearDocumentoConEstado($proyecto, $etapas[0]->cargoFirma->tipo_estado_id);
        $firmaDocumentoEliminado = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ], $documento, 3);
        $documento->delete();

        $proyectoEliminado = $this->crearProyectoConEstado($etapas[0]->cargoFirma->tipo_estado_id);
        $firmaProyectoEliminado = $this->crearFirmaDeEtapa($proyectoEliminado, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ]);
        $proyectoEliminado->delete();

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaFicha, $user));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaEtapaNull->refresh(), $user));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaDocumentoEliminado->refresh(), $user));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firmaProyectoEliminado->refresh(), $user));
    }

    public function test_rol_requerido_usa_snapshot_y_no_rol_vivo_de_la_etapa(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$userSnapshot, $empleado, $roleSnapshot] = $this->crearUsuarioEmpleadoConRol('Rol snapshot');
        [$userRolVivo, , $roleVivo] = $this->crearUsuarioEmpleadoConRol('Rol vivo');
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $roleSnapshot->name,
        ]);

        $etapas[0]->update(['rol_revisor_id' => $roleVivo->id]);

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $userSnapshot));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $userRolVivo));
    }

    public function test_no_se_selecciona_primer_usuario_global_del_rol(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        $role = $this->crearRol('Rol global');
        [$primerUsuario] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        [, $empleadoAsignado] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleadoAsignado, [
            'rol_requerido' => $role->name,
        ]);

        $this->assertFalse($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $primerUsuario));
    }

    public function test_metodo_no_modifica_firma_proyecto_documento_ni_estados(): void
    {
        [$proyecto, , $etapas] = $this->crearProyectoConFlujo();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol sin mutacion');
        $documento = $this->crearDocumentoConEstado($proyecto, $etapas[0]->cargoFirma->tipo_estado_id);
        $firma = $this->crearFirmaDeEtapa($proyecto, $etapas[0], $empleado, [
            'rol_requerido' => $role->name,
        ], $documento);
        $firmaOriginal = $firma->getAttributes();
        $proyectoOriginal = $proyecto->fresh()->getAttributes();
        $documentoOriginal = $documento->fresh()->getAttributes();
        $estadoProyectoCount = $proyecto->estado_proyecto()->count();
        $estadoDocumentoCount = $documento->estado_documento()->count();

        $this->assertTrue($this->componenteAutorizacion()->puedeActuarPorEtapa($firma, $user));

        $firmaActual = $firma->fresh();
        $this->assertSame($firmaOriginal['estado_revision'], $firmaActual->estado_revision);
        $this->assertSame($firmaOriginal['updated_at'], $firmaActual->getRawOriginal('updated_at'));
        $this->assertSame($proyectoOriginal, $proyecto->fresh()->getAttributes());
        $this->assertSame($documentoOriginal, $documento->fresh()->getAttributes());
        $this->assertSame($estadoProyectoCount, $proyecto->estado_proyecto()->count());
        $this->assertSame($estadoDocumentoCount, $documento->estado_documento()->count());
    }

    public function test_can_act_on_firma_legacy_conserva_comportamiento(): void
    {
        [$proyecto, , , $cargo] = $this->crearProyectoConFlujo();
        [$user, $empleado] = $this->crearUsuarioEmpleadoConRol('Rol legacy');
        $firma = $this->crearFirmaLegacy($proyecto, $cargo, $empleado);

        $user->forceFill(['active_role_id' => null])->save();
        $this->actingAs($user->fresh());

        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'canActOnFirma');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(new ProyectosPorFirmar, $firma));
    }

    private function componenteAutorizacion(): ProyectosPorFirmarWorkflowStageAuthorizationComponent
    {
        return new ProyectosPorFirmarWorkflowStageAuthorizationComponent;
    }

    private function crearProyectoConFlujo(int $cantidadEtapas = 1): array
    {
        $estado = $this->crearTipoEstado();
        $cargo = $this->crearCargoFirma($estado->id);
        $proyecto = $this->crearProyectoConEstado($estado->id);
        $flujo = $this->crearFlujo();
        $etapas = [];

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $etapas[] = $this->crearEtapa($flujo, $cargo, $orden);
        }

        return [$proyecto, $flujo, $etapas, $cargo, $estado];
    }

    private function crearProyectoConEstado(int $tipoEstadoId): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto autorización '.uniqid(),
            'codigo_proyecto' => 'AUTH-'.uniqid(),
        ]);
        $empleado = $this->crearEmpleado();
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return $proyecto;
    }

    private function crearDocumentoConEstado(Proyecto $proyecto, int $tipoEstadoId): DocumentoProyecto
    {
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Intermedio',
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $empleado = $this->crearEmpleado();
        $documento->estado_documento()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return $documento;
    }

    private function crearFirmaDeEtapa(
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
            'hash' => 'hash-test',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'responsable_usuario_id' => null,
            'rol_requerido' => null,
            'revision_ciclo' => $revisionCiclo,
        ], $attributes));
    }

    private function crearFirmaLegacy(
        Proyecto $proyecto,
        CargoFirma $cargo,
        Empleado $empleado,
        array $attributes = []
    ): FirmaProyecto {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
        ], $attributes));
    }

    private function crearUsuarioEmpleadoConRol(string $nombreRol, ?Role $role = null, bool $assignRole = true): array
    {
        $role = $role ?: $this->crearRol($nombreRol);
        $user = $this->crearUsuario();
        $empleado = $this->crearEmpleado($user);

        if ($assignRole) {
            $user->assignRole($role);
        }

        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function crearUsuario(): User
    {
        return User::create([
            'name' => 'Usuario autorización',
            'email' => 'auth-'.uniqid().'@unah.test',
        ]);
    }

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: $this->crearUsuario();

        return Empleado::create([
            'nombre_completo' => 'Empleado autorización',
            'numero_empleado' => 'AUTH-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function crearRol(string $nombre): Role
    {
        return Role::create([
            'name' => $nombre.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function crearTipoEstado(string $nombre = 'Estado autorización'): TipoEstado
    {
        return TipoEstado::create([
            'nombre' => $nombre.' '.uniqid(),
        ]);
    }

    private function crearCargoFirma(int $tipoEstadoId): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create([
            'nombre' => 'Cargo autorización '.uniqid(),
        ]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }

    private function crearFlujo(): FlujoAprobacion
    {
        return FlujoAprobacion::create([
            'codigo' => 'FLUJO_AUTH_'.uniqid(),
            'nombre' => 'Flujo autorización',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
    }

    private function crearEtapa(FlujoAprobacion $flujo, CargoFirma $cargo, int $orden): FlujoAprobacionEtapa
    {
        return FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => $orden,
            'codigo' => 'AUTH_ETAPA_'.$orden.'_'.uniqid(),
            'nombre' => 'Etapa autorización '.$orden,
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
        ]);
    }
}

class ProyectosPorFirmarWorkflowStageAuthorizationComponent extends ProyectosPorFirmar
{
    public function puedeActuarPorEtapa(FirmaProyecto $firma, ?User $user = null): bool
    {
        return $this->canActOnWorkflowStageFirma($firma, $user);
    }
}
