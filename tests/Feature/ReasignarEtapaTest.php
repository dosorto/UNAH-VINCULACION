<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReasignarEtapaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_responsable_actual_reasigna_a_otro_usuario_con_el_mismo_rol(): void
    {
        [$proyecto, $etapa] = $this->crearContexto();
        [$responsableUser, $responsableEmpleado, $role] = $this->crearUsuarioEmpleadoConRol('Revisor Vinculacion');
        [$nuevoUser, $nuevoEmpleado] = $this->crearUsuarioEmpleadoConRol($role->name, $role);

        $firma = $this->crearFirmaDeEtapa($proyecto, $etapa, $responsableEmpleado, [
            'rol_requerido' => $role->name,
            'responsable_usuario_id' => $responsableUser->id,
        ]);

        $firma->reasignarA($nuevoUser, $responsableUser);
        $firma->refresh();

        $this->assertSame($nuevoUser->id, $firma->responsable_usuario_id);
        $this->assertSame($nuevoEmpleado->id, $firma->empleado_id);

        // el responsable anterior ya no puede actuar; el nuevo sí
        $comp = new ProyectosPorFirmarWorkflowStageComponent();
        $this->assertFalse($comp->canActOnWorkflowStageFirmaPublico($firma, $responsableUser));
        $this->assertTrue($comp->canActOnWorkflowStageFirmaPublico($firma, $nuevoUser));
    }

    public function test_reasignar_a_usuario_sin_el_rol_falla(): void
    {
        [$proyecto, $etapa] = $this->crearContexto();
        [$responsableUser, $responsableEmpleado, $role] = $this->crearUsuarioEmpleadoConRol('Revisor Vinculacion');
        [$otroUser] = $this->crearUsuarioEmpleadoConRol('Otro rol sin permiso');

        $firma = $this->crearFirmaDeEtapa($proyecto, $etapa, $responsableEmpleado, [
            'rol_requerido' => $role->name,
            'responsable_usuario_id' => $responsableUser->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no tiene el rol');

        $firma->reasignarA($otroUser, $responsableUser);
    }

    public function test_solo_el_responsable_actual_puede_reasignar(): void
    {
        [$proyecto, $etapa] = $this->crearContexto();
        [$responsableUser, $responsableEmpleado, $role] = $this->crearUsuarioEmpleadoConRol('Revisor Vinculacion');
        [$nuevoUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        [$intrusoUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);

        $firma = $this->crearFirmaDeEtapa($proyecto, $etapa, $responsableEmpleado, [
            'rol_requerido' => $role->name,
            'responsable_usuario_id' => $responsableUser->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Solo el responsable actual');

        $firma->reasignarA($nuevoUser, $intrusoUser);
    }

    private function crearContexto(): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto reasignacion '.uniqid(),
            'codigo_proyecto' => 'REAS-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_REAS_'.uniqid(),
            'nombre' => 'Flujo reasignacion',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estado = TipoEstado::create(['nombre' => 'Estado reasignacion '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo reasignacion '.uniqid()]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $estado->id,
        ]);
        $etapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 1,
            'codigo' => 'REAS_ETAPA_1_'.uniqid(),
            'nombre' => 'Etapa reasignacion',
            'cargo_firma_id' => $cargo->id,
            'requiere_asignacion' => true,
            'activo' => true,
        ]);

        $empleadoEstado = Empleado::create([
            'nombre_completo' => 'Empleado estado reasignacion',
            'numero_empleado' => 'REAS-EST-'.uniqid(),
            'celular' => '99999999',
            'user_id' => User::create([
                'name' => 'Usuario estado reasignacion',
                'email' => 'reas-estado-'.uniqid().'@unah.test',
            ])->id,
        ]);

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleadoEstado->id,
            'tipo_estado_id' => $estado->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return [$proyecto, $etapa];
    }

    private function crearFirmaDeEtapa(Proyecto $proyecto, FlujoAprobacionEtapa $etapa, Empleado $empleado, array $attributes = []): FirmaProyecto
    {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'revision_ciclo' => 1,
        ], $attributes));
    }

    private function crearUsuarioEmpleadoConRol(string $nombreRol, ?Role $role = null): array
    {
        $role = $role ?: Role::create(['name' => $nombreRol.' '.uniqid(), 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Usuario reasignacion',
            'email' => 'reas-'.uniqid().'@unah.test',
        ]);
        $empleado = Empleado::create([
            'nombre_completo' => 'Empleado reasignacion',
            'numero_empleado' => 'REAS-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
        $user->assignRole($role);
        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }
}

class ProyectosPorFirmarWorkflowStageComponent extends ProyectosPorFirmar
{
    public function canActOnWorkflowStageFirmaPublico(FirmaProyecto $firma, User $user): bool
    {
        return $this->canActOnWorkflowStageFirma($firma, $user);
    }
}
