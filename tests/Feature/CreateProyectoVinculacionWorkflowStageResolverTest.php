<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProyectoVinculacionWorkflowStageResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_obtiene_etapas_activas_en_orden_ignora_inactivas_y_no_identifica_por_cargo(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol etapas');
        $proyecto = $this->proyecto($context['flujo']);
        $etapaDos = $this->etapa($context, ['orden' => 2, 'nombre' => 'Segunda etapa', 'rol_revisor_id' => $role->id]);
        $etapaUno = $this->etapa($context, ['orden' => 1, 'nombre' => 'Primera etapa', 'rol_revisor_id' => $role->id]);
        $this->etapa($context, ['orden' => 3, 'nombre' => 'Inactiva', 'rol_revisor_id' => $role->id, 'activo' => false]);

        $etapas = $component->etapasParaEnvio($proyecto);

        $this->assertSame([$etapaUno->id, $etapaDos->id], $etapas->pluck('id')->all());
        $this->assertSame([$context['cargo']->id, $context['cargo']->id], $etapas->pluck('cargo_firma_id')->all());
    }

    public function test_falla_si_no_hay_etapas_activas_o_si_etapa_activa_esta_mal_configurada(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $proyecto = $this->proyecto($context['flujo']);
        $role = $this->role('Rol errores');

        $this->assertRuntime(
            fn () => $component->etapasParaEnvio($proyecto),
            'No hay etapas activas configuradas para enviar este proyecto a revisión.'
        );

        $sinCargo = new FlujoAprobacionEtapa([
            'nombre' => 'Sin cargo',
            'activo' => true,
            'rol_revisor_id' => $role->id,
            'cargo_firma_id' => null,
        ]);
        $proyectoConEtapaSinCargo = new class extends Proyecto {
            private FlujoAprobacionEtapa $stage;

            public function setStage(FlujoAprobacionEtapa $stage): self
            {
                $this->stage = $stage;

                return $this;
            }

            public function flujoEtapasActivasOrdenadas(?string $proceso = null): Collection
            {
                return collect([$this->stage]);
            }
        };
        $proyectoConEtapaSinCargo->setStage($sinCargo);

        $this->assertRuntime(
            fn () => $component->etapasParaEnvio($proyectoConEtapaSinCargo),
            'no tiene cargo de firma configurado'
        );

        $this->etapa($context, ['nombre' => 'Sin responsable', 'rol_revisor_id' => null, 'usuario_responsable_id' => null]);
        $proyecto = $proyecto->fresh();

        $this->assertRuntime(
            fn () => $component->etapasParaEnvio($proyecto),
            'no tiene rol revisor ni responsable configurado'
        );
    }

    public function test_resuelve_candidatos_por_etapa_para_todos_los_alcances_sin_autoseleccionar(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $centro = $this->centro('Centro etapa');
        $depto = $this->departamento($centro, 'Departamento etapa');
        $carrera = $this->carrera($centro, $depto, 'Carrera etapa');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centro], departamentos: [$depto], carreras: [$carrera]);

        $sinFiltro = $this->role('Rol sin filtro');
        $global = $this->role('Rol global');
        $centroRole = $this->role('Rol centro');
        $deptoRole = $this->role('Rol depto');
        $carreraRole = $this->role('Rol carrera');
        $proyectoRole = $this->role('Rol proyecto');

        $etapaSinFiltro = $this->etapa($context, ['orden' => 1, 'nombre' => 'Sin filtro', 'rol_revisor_id' => $sinFiltro->id]);
        $etapaGlobal = $this->etapa($context, ['orden' => 2, 'nombre' => 'Global', 'rol_revisor_id' => $global->id]);
        $etapaCentro = $this->etapa($context, ['orden' => 3, 'nombre' => 'Centro', 'rol_revisor_id' => $centroRole->id]);
        $etapaDepto = $this->etapa($context, ['orden' => 4, 'nombre' => 'Departamento', 'rol_revisor_id' => $deptoRole->id]);
        $etapaCarrera = $this->etapa($context, ['orden' => 5, 'nombre' => 'Carrera', 'rol_revisor_id' => $carreraRole->id]);
        $etapaProyecto = $this->etapa($context, ['orden' => 6, 'nombre' => 'Proyecto', 'rol_revisor_id' => $proyectoRole->id]);

        [, $sinFiltroUno] = $this->usuarioEmpleado('Sin Filtro Uno', $sinFiltro);
        [, $sinFiltroDos] = $this->usuarioEmpleado('Sin Filtro Dos', $sinFiltro);
        [, $globalEmpleado] = $this->usuarioEmpleado('Global Uno', $global);
        [, $centroEmpleado] = $this->usuarioEmpleado('Centro Uno', $centroRole, centro: $centro);
        $this->usuarioEmpleado('Centro Ajeno', $centroRole, centro: $this->centro('Centro ajeno'));
        [, $deptoEmpleado] = $this->usuarioEmpleado('Depto Uno', $deptoRole, centro: $centro, departamento: $depto);
        $this->usuarioEmpleado('Depto Ajeno', $deptoRole, centro: $centro, departamento: $this->departamento($centro, 'Departamento ajeno'));
        [, $carreraEmpleado] = $this->usuarioEmpleado('Carrera Uno', $carreraRole, centro: $centro, departamento: $depto, carrera: $carrera);
        $this->usuarioEmpleado('Carrera Ajena', $carreraRole, centro: $centro, departamento: $depto, carrera: $this->carrera($centro, $depto, 'Carrera ajena'));
        [, $coordinador] = $this->usuarioEmpleado('Coordinador proyecto', $proyectoRole);
        $this->usuarioEmpleado('Proyecto no coordinador', $proyectoRole);
        $this->vincularCoordinador($proyecto, $coordinador);

        $resultado = $component->candidatosParaEnvio($proyecto)->keyBy(fn (array $grupo) => $grupo['etapa']->id);

        $this->assertEqualsCanonicalizing([$sinFiltroUno->id, $sinFiltroDos->id], $resultado[$etapaSinFiltro->id]['candidatos']->pluck('id')->all());
        $this->assertSame([$globalEmpleado->id], $resultado[$etapaGlobal->id]['candidatos']->pluck('id')->all());
        $this->assertSame([$centroEmpleado->id], $resultado[$etapaCentro->id]['candidatos']->pluck('id')->all());
        $this->assertSame([$deptoEmpleado->id], $resultado[$etapaDepto->id]['candidatos']->pluck('id')->all());
        $this->assertSame([$carreraEmpleado->id], $resultado[$etapaCarrera->id]['candidatos']->pluck('id')->all());
        $this->assertSame([$coordinador->id], $resultado[$etapaProyecto->id]['candidatos']->pluck('id')->all());
        $this->assertNull($component->jefe_empleado_id);
        $this->assertNull($component->decano_empleado_id);
        $this->assertNull($component->enlace_empleado_id);
    }

    public function test_reporta_unidades_sin_candidatos_para_etapas_por_unidad(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol unidades');
        $centroUno = $this->centro('Centro con candidato');
        $centroDos = $this->centro('Centro sin candidato');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centroUno, $centroDos]);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
        ]);
        [, $empleado] = $this->usuarioEmpleado('Centro candidato', $role, centro: $centroUno);

        $grupo = $component->candidatosParaEnvio($proyecto)->first();
        $unidadesSinCandidatos = $component->unidadesSinCandidatos($proyecto);

        $this->assertSame($etapa->id, $grupo['etapa']->id);
        $this->assertSame([$empleado->id], $grupo['candidatos']->pluck('id')->all());
        $this->assertSame([$centroDos->id], $grupo['unidades_sin_candidatos']->pluck('unidad_id')->all());
        $this->assertSame($etapa->id, $unidadesSinCandidatos->first()['etapa']->id);
        $this->assertSame($centroDos->id, $unidadesSinCandidatos->first()['unidad']['unidad_id']);
    }

    public function test_valida_empleado_elegible_por_etapa_y_rechaza_etapa_ajena(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol validar');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);
        [, $empleado] = $this->usuarioEmpleado('Empleado elegible', $role);
        $otroContext = $this->contexto();
        $etapaAjena = $this->etapa($otroContext, ['rol_revisor_id' => $role->id]);

        $validado = $component->validarEmpleado($proyecto, $etapa->id, $empleado->id);

        $this->assertSame($empleado->id, $validado->id);
        $this->assertRuntime(
            fn () => $component->validarEmpleado($proyecto, $etapaAjena->id, $empleado->id),
            'La etapa indicada no pertenece al flujo del proyecto.'
        );
    }

    public function test_rechaza_empleados_no_elegibles_por_alcance_rol_activo_o_usuario_sin_empleado(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol elegibilidad');
        $otroRol = $this->role('Rol active incorrecto');
        $centro = $this->centro('Centro valido');
        $centroAjeno = $this->centro('Centro externo');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centro]);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
        ]);
        [, $empleadoAjeno] = $this->usuarioEmpleado('Empleado centro ajeno', $role, centro: $centroAjeno);
        [, $activeIncorrecto] = $this->usuarioEmpleado('Empleado active incorrecto', $role, activeRole: $otroRol, centro: $centro);
        $sinEmpleado = User::create([
            'name' => 'Usuario sin empleado',
            'email' => 'sin-empleado-'.uniqid().'@test.local',
            'active_role_id' => $role->id,
        ]);
        $sinEmpleado->assignRole($role);
        $etapaResponsableSinEmpleado = $this->etapa($context, [
            'orden' => 2,
            'rol_revisor_id' => $role->id,
            'usuario_responsable_id' => $sinEmpleado->id,
        ]);

        $this->assertRuntime(
            fn () => $component->validarEmpleado($proyecto, $etapa->id, $empleadoAjeno->id),
            'El empleado seleccionado no es elegible para la etapa'
        );
        $this->assertRuntime(
            fn () => $component->validarEmpleado($proyecto, $etapa->id, $activeIncorrecto->id),
            'El empleado seleccionado no es elegible para la etapa'
        );
        $this->assertTrue($component->candidatosParaEnvio($proyecto)
            ->first(fn (array $grupo) => $grupo['etapa']->id === $etapaResponsableSinEmpleado->id)['candidatos']
            ->isEmpty());
    }

    public function test_valida_asignaciones_completas_y_rechaza_faltantes_o_etapas_extra(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol asignaciones');
        $proyecto = $this->proyecto($context['flujo']);
        $etapaUno = $this->etapa($context, ['orden' => 1, 'nombre' => 'Etapa uno', 'rol_revisor_id' => $role->id]);
        $etapaDos = $this->etapa($context, ['orden' => 2, 'nombre' => 'Etapa dos', 'rol_revisor_id' => $role->id]);
        [, $empleadoUno] = $this->usuarioEmpleado('Asignado uno', $role);
        [, $empleadoDos] = $this->usuarioEmpleado('Asignado dos', $role);

        $normalizadas = $component->validarAsignaciones($proyecto, [
            (string) $etapaUno->id => (string) $empleadoUno->id,
            (string) $etapaDos->id => (string) $empleadoDos->id,
        ]);

        $this->assertSame([
            $etapaUno->id => $empleadoUno->id,
            $etapaDos->id => $empleadoDos->id,
        ], $normalizadas);
        $this->assertRuntime(
            fn () => $component->validarAsignaciones($proyecto, [$etapaUno->id => $empleadoUno->id]),
            'No se indicó un empleado para la etapa "Etapa dos".'
        );
        $this->assertRuntime(
            fn () => $component->validarAsignaciones($proyecto, [
                $etapaUno->id => $empleadoUno->id,
                $etapaDos->id => $empleadoDos->id,
                999999 => $empleadoUno->id,
            ]),
            'La etapa indicada no pertenece al flujo del proyecto.'
        );
    }

    public function test_asignaciones_fallan_con_empleado_no_elegible_y_por_cada_unidad_no_integrado(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $context = $this->contexto();
        $role = $this->role('Rol por unidad');
        $centro = $this->centro('Centro por unidad');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centro]);
        $etapa = $this->etapa($context, ['nombre' => 'Etapa unica', 'rol_revisor_id' => $role->id]);
        [, $empleadoOtroRol] = $this->usuarioEmpleado('No elegible asignacion', $this->role('Otro rol asignacion'));

        $this->assertRuntime(
            fn () => $component->validarAsignaciones($proyecto, [$etapa->id => $empleadoOtroRol->id]),
            'El empleado seleccionado no es elegible para la etapa "Etapa unica".'
        );

        $proyecto = $proyecto->fresh();

        $this->assertRuntime(
            fn () => $component->validarAsignaciones($proyecto, [$etapa->id => $empleadoOtroRol->id]),
            'requiere un revisor por unidad académica y aún no está integrada al formulario de envío'
        );
    }

    public function test_helpers_no_modifican_proyecto_no_crean_firmas_estados_ni_tocan_propiedades_legacy(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageResolverComponent;
        $component->jefe_empleado_id = 101;
        $component->decano_empleado_id = 202;
        $component->enlace_empleado_id = 303;
        $context = $this->contexto();
        $role = $this->role('Rol sin efectos');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);
        [, $empleado] = $this->usuarioEmpleado('Empleado sin efectos', $role);
        $snapshotProyecto = $proyecto->fresh()->getAttributes();
        $firmas = FirmaProyecto::count();
        $estados = DB::table('estado_proyecto')->count();

        $component->etapasParaEnvio($proyecto);
        $component->candidatosParaEnvio($proyecto);
        $component->unidadesSinCandidatos($proyecto);
        $component->validarEmpleado($proyecto, $etapa->id, $empleado->id);
        $component->validarAsignaciones($proyecto, [$etapa->id => $empleado->id]);

        $this->assertSame($snapshotProyecto, $proyecto->fresh()->getAttributes());
        $this->assertSame($firmas, FirmaProyecto::count());
        $this->assertSame($estados, DB::table('estado_proyecto')->count());
        $this->assertSame(101, $component->jefe_empleado_id);
        $this->assertSame(202, $component->decano_empleado_id);
        $this->assertSame(303, $component->enlace_empleado_id);
        $this->assertFalse($component->saveFirmasLlamado);
    }

    private function assertRuntime(callable $callback, string $mensaje): void
    {
        try {
            $callback();
            $this->fail('Se esperaba una RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString($mensaje, $exception->getMessage());
        }
    }

    private function contexto(): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_CREATE_'.uniqid(),
            'nombre' => 'Flujo create',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado create '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo create '.uniqid()]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $estado->id,
        ]);

        return compact('flujo', 'cargo');
    }

    private function etapa(array $context, array $attributes = []): FlujoAprobacionEtapa
    {
        return $context['flujo']->etapas()->create(array_merge([
            'orden' => (int) ($attributes['orden'] ?? random_int(1, 100000)),
            'codigo' => 'ETAPA_'.uniqid(),
            'nombre' => 'Etapa create',
            'cargo_firma_id' => $context['cargo']->id,
            'activo' => true,
        ], $attributes));
    }

    private function proyecto(
        ?FlujoAprobacion $flujo = null,
        array $centros = [],
        array $departamentos = [],
        array $carreras = []
    ): Proyecto {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto create '.uniqid(),
            'codigo_proyecto' => 'CRT-'.uniqid(),
        ]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo?->id])->save();
        $proyecto->facultades_centros()->sync(collect($centros)->pluck('id')->all());
        $proyecto->departamentos_academicos()->sync(collect($departamentos)->pluck('id')->all());
        $proyecto->carreras()->sync(collect($carreras)->pluck('id')->all());

        return $proyecto->fresh();
    }

    private function role(string $name): Role
    {
        return Role::create([
            'name' => $name.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function usuarioEmpleado(
        string $name,
        ?Role $role = null,
        ?Role $activeRole = null,
        ?FacultadCentro $centro = null,
        ?DepartamentoAcademico $departamento = null,
        ?Carrera $carrera = null
    ): array {
        $activeRole = $activeRole ?: $role;
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@test.local',
            'active_role_id' => $activeRole?->id,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        $empleado = Empleado::create([
            'nombre_completo' => $name,
            'numero_empleado' => 'EMP-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
            'centro_facultad_id' => $centro?->id,
            'departamento_academico_id' => $departamento?->id,
        ]);
        $empleado->forceFill(['carrera_id' => $carrera?->id])->save();

        return [$user->fresh('roles', 'empleado'), $empleado->fresh('user.roles')];
    }

    private function campus(): Campus
    {
        return Campus::create([
            'nombre_campus' => 'Campus '.uniqid(),
            'siglas' => 'CMP',
            'direccion' => 'Direccion',
            'telefono' => '0000-0000',
            'url' => 'https://unah.test',
        ]);
    }

    private function centro(string $nombre): FacultadCentro
    {
        return FacultadCentro::create([
            'nombre' => $nombre.' '.uniqid(),
            'es_facultad' => true,
            'siglas' => 'FC'.random_int(100, 999),
            'campus_id' => $this->campus()->id,
        ]);
    }

    private function departamento(FacultadCentro $centro, string $nombre): DepartamentoAcademico
    {
        return DepartamentoAcademico::create([
            'nombre' => $nombre.' '.uniqid(),
            'siglas' => 'DA'.random_int(100, 999),
            'centro_facultad_id' => $centro->id,
        ]);
    }

    private function carrera(FacultadCentro $centro, DepartamentoAcademico $departamento, string $nombre): Carrera
    {
        return Carrera::create([
            'nombre' => $nombre.' '.uniqid(),
            'siglas' => 'CR'.random_int(100, 999),
            'facultad_centro_id' => $centro->id,
            'departamento_academico_id' => $departamento->id,
        ]);
    }

    private function vincularCoordinador(Proyecto $proyecto, Empleado $empleado): void
    {
        DB::table('empleado_proyecto')->insert([
            'empleado_id' => $empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
            'hash' => 'hash-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

class CreateProyectoVinculacionWorkflowStageResolverComponent extends CreateProyectoVinculacion
{
    public bool $saveFirmasLlamado = false;

    public function etapasParaEnvio(Proyecto $proyecto): Collection
    {
        return $this->etapasActivasParaEnvioPorFlujo($proyecto);
    }

    public function candidatosParaEnvio(Proyecto $proyecto): Collection
    {
        return $this->candidatosPorEtapaParaEnvio($proyecto);
    }

    public function unidadesSinCandidatos(Proyecto $proyecto): Collection
    {
        return $this->unidadesSinCandidatosParaEnvio($proyecto);
    }

    public function validarEmpleado(Proyecto $proyecto, int $etapaId, int $empleadoId): Empleado
    {
        return $this->validarEmpleadoParaEtapaDeEnvio($proyecto, $etapaId, $empleadoId);
    }

    public function validarAsignaciones(Proyecto $proyecto, array $empleadosPorEtapa): array
    {
        return $this->validarAsignacionesPorEtapaParaEnvio($proyecto, $empleadosPorEtapa);
    }

    protected function saveFirmas(Proyecto $record): void
    {
        $this->saveFirmasLlamado = true;
    }
}
