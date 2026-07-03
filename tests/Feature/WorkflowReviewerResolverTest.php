<?php

namespace Tests\Feature;

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
use App\Services\Workflow\WorkflowReviewerResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowReviewerResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_configuracion_invalida_falla_con_mensajes_controlados(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $proyecto = $this->proyecto();
        $role = $this->role();

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['activo' => false, 'rol_revisor_id' => $role->id]),
            $proyecto
        ), 'La etapa no se encuentra activa.');

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['rol_revisor_id' => $role->id, 'alcance_academico' => 'INVENTADO']),
            $proyecto
        ), 'El alcance académico configurado para la etapa no es válido.');

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['rol_revisor_id' => $role->id, 'multiplicidad_revision' => 'MUCHOS']),
            $proyecto
        ), 'La multiplicidad configurada para la etapa no es válida.');

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context),
            $proyecto
        ), 'La etapa no tiene un rol ni un usuario responsable configurado.');

        foreach ([
            FlujoAprobacionEtapa::ALCANCE_GLOBAL,
            FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            FlujoAprobacionEtapa::ALCANCE_PROYECTO,
        ] as $alcance) {
            $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
                $this->etapa($context, [
                    'rol_revisor_id' => $role->id,
                    'alcance_academico' => $alcance,
                    'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
                ]),
                $proyecto
            ), 'La multiplicidad por unidad no es válida para el alcance configurado.');
        }

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['rol_revisor_id' => $role->id, 'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO]),
            $proyecto
        ), 'La etapa requiere centros académicos, pero el proyecto no tiene centros asociados.');

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['rol_revisor_id' => $role->id, 'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO]),
            $proyecto
        ), 'La etapa requiere departamentos académicos, pero el proyecto no tiene departamentos asociados.');

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, ['rol_revisor_id' => $role->id, 'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CARRERA]),
            $proyecto
        ), 'La etapa requiere carreras, pero el proyecto no tiene carreras asociadas.');

        $centroUno = $this->centro('Centro ambiguo uno');
        $centroDos = $this->centro('Centro ambiguo dos');
        [$responsableUser] = $this->usuarioEmpleado('Responsable ambiguo', $role, centro: $centroUno);
        $proyectoConCentros = $this->proyecto(centros: [$centroUno, $centroDos]);

        $this->assertRuntime(fn () => $resolver->candidatosParaEtapa(
            $this->etapa($context, [
                'usuario_responsable_id' => $responsableUser->id,
                'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
                'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
            ]),
            $proyectoConCentros
        ), 'Un responsable fijo no puede cubrir varias unidades académicas en esta configuración.');
    }

    public function test_reglas_de_rol_active_role_empleado_y_datos_eliminados(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $proyecto = $this->proyecto();
        $role = $this->role('Rol revisor');
        $otroRol = $this->role('Otro rol');
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);

        [, $valido] = $this->usuarioEmpleado('Ana Valida', $role);
        $this->usuarioEmpleado('Bruno Rol Inactivo', $role, activeRole: $otroRol);
        $this->usuarioEmpleado('Carla Active Sin Rol', null, activeRole: $role);
        [$sinActive] = $this->usuarioEmpleado('Diego Sin Active', $role);
        $sinActive->forceFill(['active_role_id' => null])->save();
        User::create(['name' => 'Elsa Sin Empleado', 'email' => 'sin-empleado-'.uniqid().'@test.local', 'active_role_id' => $role->id])
            ->assignRole($role);
        [, $eliminado] = $this->usuarioEmpleado('Fede Eliminado', $role);
        $eliminado->delete();
        [$userEliminado] = $this->usuarioEmpleado('Gina User Eliminado', $role);
        $userEliminado->delete();

        $candidatos = $resolver->candidatosParaEtapa($etapa, $proyecto);

        $this->assertSame([$valido->id], $candidatos->pluck('id')->all());
    }

    public function test_no_usa_cargo_o_tipo_cargo_como_rol_y_deduplica(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $proyecto = $this->proyecto();
        $role = $this->role('Rol diferente');
        $tipoCargo = TipoCargoFirma::create(['nombre' => $role->name]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Estado rol cargo'])->id,
        ]);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id, 'cargo_firma_id' => $cargo->id]);
        [, $empleado] = $this->usuarioEmpleado('Empleado cargo', null);

        $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
        ]);

        $this->assertTrue($resolver->candidatosParaEtapa($etapa, $proyecto)->isEmpty());

        [$user] = $this->usuarioEmpleado('Empleado rol real', $role);
        $user->empleado()->first()->update(['centro_facultad_id' => null]);
        $candidatos = $resolver->candidatosParaEtapa($etapa, $proyecto);

        $this->assertCount(1, $candidatos);
        $this->assertSame($user->empleado->id, $candidatos->first()->id);
    }

    public function test_responsable_fijo_no_tiene_fallback_y_valida_empleado(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $otroRol = $this->role('Otro responsable');
        $centro = $this->centro('Centro responsable');
        $proyecto = $this->proyecto(centros: [$centro]);
        [$responsableUser, $responsable] = $this->usuarioEmpleado('Responsable', $role, centro: $centro);
        [, $otro] = $this->usuarioEmpleado('Otro mismo rol', $role, centro: $centro);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'usuario_responsable_id' => $responsableUser->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
        ]);

        $candidatos = $resolver->candidatosParaEtapa($etapa, $proyecto);

        $this->assertSame([$responsable->id], $candidatos->pluck('id')->all());
        $this->assertTrue($resolver->empleadoEsElegible($etapa, $proyecto, $responsable));
        $this->assertFalse($resolver->empleadoEsElegible($etapa, $proyecto, $otro));
        $this->assertSame($responsable->id, $resolver->validarEmpleadoElegible($etapa, $proyecto, $responsable->id)->id);
        $this->assertRuntime(fn () => $resolver->validarEmpleadoElegible($etapa, $proyecto, $otro), 'El empleado seleccionado no es elegible');

        $responsableUser->forceFill(['active_role_id' => $otroRol->id])->save();
        $this->assertTrue($resolver->candidatosParaEtapa($etapa, $proyecto)->isEmpty());

        $centroAjeno = $this->centro('Centro ajeno');
        $responsableUser->forceFill(['active_role_id' => $role->id])->save();
        $responsable->update(['centro_facultad_id' => $centroAjeno->id]);
        $this->assertTrue($resolver->candidatosParaEtapa($etapa, $proyecto)->isEmpty());

        $sinEmpleado = User::create([
            'name' => 'Responsable sin empleado',
            'email' => 'responsable-sin-empleado-'.uniqid().'@test.local',
            'active_role_id' => $role->id,
        ]);
        $sinEmpleado->assignRole($role);
        $etapaSinEmpleado = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'usuario_responsable_id' => $sinEmpleado->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
        ]);

        $this->assertTrue($resolver->candidatosParaEtapa($etapaSinEmpleado, $proyecto)->isEmpty());
    }

    public function test_sin_filtro_y_global_devuelven_todos_los_candidatos_ordenados_sin_filtrar_academicamente(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $centroA = $this->centro('Centro A');
        $centroB = $this->centro('Centro B');
        $proyecto = $this->proyecto(centros: [$centroA]);
        [, $zoe] = $this->usuarioEmpleado('Zoe', $role, centro: $centroB);
        [, $ana] = $this->usuarioEmpleado('Ana', $role, centro: $centroA);
        [, $mario] = $this->usuarioEmpleado('Mario', $role);

        $sinFiltro = $resolver->candidatosParaEtapa($this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
        ]), $proyecto);
        $global = $resolver->candidatosParaEtapa($this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_GLOBAL,
        ]), $proyecto);

        $this->assertSame([$ana->id, $mario->id, $zoe->id], $sinFiltro->pluck('id')->all());
        $this->assertSame([$ana->id, $mario->id, $zoe->id], $global->pluck('id')->all());
        $this->assertSame([], $resolver->candidatosPorUnidadParaEtapa($this->etapa($context, ['rol_revisor_id' => $role->id]), $proyecto)->all());
    }

    public function test_centro_filtra_y_agrupa_por_unidad_incluyendo_unidades_sin_candidatos(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $centroUno = $this->centro('Centro uno');
        $centroDos = $this->centro('Centro dos');
        $centroTres = $this->centro('Centro tres');
        $proyecto = $this->proyecto(centros: [$centroUno, $centroDos]);
        [, $empleadoUno] = $this->usuarioEmpleado('Centro Uno', $role, centro: $centroUno);
        $this->usuarioEmpleado('Centro Tres', $role, centro: $centroTres);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);

        $this->assertSame([$empleadoUno->id], $resolver->candidatosParaEtapa($etapa, $proyecto)->pluck('id')->all());

        $grupos = $resolver->candidatosPorUnidadParaEtapa($etapa, $proyecto);
        $gruposPorUnidad = $grupos->keyBy('unidad_id');
        $this->assertCount(2, $grupos);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_CENTRO, $gruposPorUnidad[$centroUno->id]['tipo']);
        $this->assertSame([$empleadoUno->id], $gruposPorUnidad[$centroUno->id]['candidatos']->pluck('id')->all());
        $this->assertTrue($gruposPorUnidad[$centroDos->id]['candidatos']->isEmpty());
        $this->assertSame([$centroDos->id], $resolver->unidadesSinCandidatos($etapa, $proyecto)->pluck('unidad_id')->all());
    }

    public function test_departamento_filtra_centro_compatible_y_agrupa(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $centroUno = $this->centro('Centro depto uno');
        $centroDos = $this->centro('Centro depto dos');
        $deptoUno = $this->departamento($centroUno, 'Depto uno');
        $deptoDos = $this->departamento($centroDos, 'Depto dos');
        $proyecto = $this->proyecto(centros: [$centroUno], departamentos: [$deptoUno, $deptoDos]);
        [, $empleadoUno] = $this->usuarioEmpleado('Depto Uno', $role, centro: $centroUno, departamento: $deptoUno);
        $this->usuarioEmpleado('Depto Dos incompatible', $role, centro: $centroDos, departamento: $deptoDos);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);

        $this->assertSame([$empleadoUno->id], $resolver->candidatosParaEtapa($etapa, $proyecto)->pluck('id')->all());
        $grupos = $resolver->candidatosPorUnidadParaEtapa($etapa, $proyecto);
        $gruposPorUnidad = $grupos->keyBy('unidad_id');

        $this->assertSame([$empleadoUno->id], $gruposPorUnidad[$deptoUno->id]['candidatos']->pluck('id')->all());
        $this->assertTrue($gruposPorUnidad[$deptoDos->id]['candidatos']->isEmpty());
        $this->assertSame([$deptoDos->id], $resolver->unidadesSinCandidatos($etapa, $proyecto)->pluck('unidad_id')->all());
    }

    public function test_carrera_usa_relacion_real_y_no_aproxima_por_departamento(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $centro = $this->centro('Centro carrera');
        $depto = $this->departamento($centro, 'Depto carrera');
        $carreraUno = $this->carrera($centro, $depto, 'Carrera uno');
        $carreraDos = $this->carrera($centro, $depto, 'Carrera dos');
        $proyecto = $this->proyecto(carreras: [$carreraUno, $carreraDos]);
        [, $empleadoUno] = $this->usuarioEmpleado('Carrera Uno', $role, centro: $centro, departamento: $depto, carrera: $carreraUno);
        $this->usuarioEmpleado('Mismo depto otra carrera', $role, centro: $centro, departamento: $depto, carrera: $this->carrera($centro, $depto, 'Carrera fuera'));
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CARRERA,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);

        $this->assertSame([$empleadoUno->id], $resolver->candidatosParaEtapa($etapa, $proyecto)->pluck('id')->all());
        $grupos = $resolver->candidatosPorUnidadParaEtapa($etapa, $proyecto);
        $gruposPorUnidad = $grupos->keyBy('unidad_id');

        $this->assertSame([$empleadoUno->id], $gruposPorUnidad[$carreraUno->id]['candidatos']->pluck('id')->all());
        $this->assertTrue($gruposPorUnidad[$carreraDos->id]['candidatos']->isEmpty());
        $this->assertSame([$carreraDos->id], $resolver->unidadesSinCandidatos($etapa, $proyecto)->pluck('unidad_id')->all());
    }

    public function test_proyecto_devuelve_solo_coordinador_o_responsable_real(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $otroRol = $this->role('Rol proyecto otro');
        $proyecto = $this->proyecto();
        [$coordinadorUser, $coordinador] = $this->usuarioEmpleado('Coordinador', $role);
        [, $otroMismoRol] = $this->usuarioEmpleado('Otro mismo rol proyecto', $role);
        $this->vincularCoordinador($proyecto, $coordinador);
        $etapa = $this->etapa($context, [
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_PROYECTO,
        ]);

        $this->assertSame([$coordinador->id], $resolver->candidatosParaEtapa($etapa, $proyecto)->pluck('id')->all());
        $this->assertFalse($resolver->empleadoEsElegible($etapa, $proyecto, $otroMismoRol));

        $coordinadorUser->forceFill(['active_role_id' => $otroRol->id])->save();
        $this->assertTrue($resolver->candidatosParaEtapa($etapa, $proyecto)->isEmpty());

        $etapaResponsable = $this->etapa($context, [
            'usuario_responsable_id' => $coordinadorUser->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_PROYECTO,
        ]);
        $this->assertSame([$coordinador->id], $resolver->candidatosParaEtapa($etapaResponsable, $proyecto)->pluck('id')->all());
    }

    public function test_resolver_no_modifica_datos_ni_crea_firmas_estados_notificaciones_o_roles(): void
    {
        $resolver = new WorkflowReviewerResolver;
        $context = $this->contexto();
        $role = $this->role();
        $proyecto = $this->proyecto();
        [$user, $empleado] = $this->usuarioEmpleado('Sin efectos', $role);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);
        $snapshotUser = $user->fresh()->getAttributes();
        $snapshotEmpleado = $empleado->fresh()->getAttributes();
        $snapshotProyecto = $proyecto->fresh()->getAttributes();
        $snapshotEtapa = $etapa->fresh()->getAttributes();
        $firmas = FirmaProyecto::count();
        $estados = DB::table('estado_proyecto')->count();
        Mail::fake();

        $resolver->candidatosParaEtapa($etapa, $proyecto);
        $resolver->empleadoEsElegible($etapa, $proyecto, $empleado->id);

        $this->assertSame($snapshotUser, $user->fresh()->getAttributes());
        $this->assertSame($snapshotEmpleado, $empleado->fresh()->getAttributes());
        $this->assertSame($snapshotProyecto, $proyecto->fresh()->getAttributes());
        $this->assertSame($snapshotEtapa, $etapa->fresh()->getAttributes());
        $this->assertSame($firmas, FirmaProyecto::count());
        $this->assertSame($estados, DB::table('estado_proyecto')->count());
        $this->assertTrue($user->roles()->whereKey($role->id)->exists());
        Mail::assertNothingSent();
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
            'codigo' => 'FLUJO_RESOLVER_'.uniqid(),
            'nombre' => 'Flujo resolver',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado resolver '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo resolver '.uniqid()]);
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
            'nombre' => 'Etapa resolver',
            'cargo_firma_id' => $context['cargo']->id,
            'activo' => true,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
        ], $attributes));
    }

    private function proyecto(array $centros = [], array $departamentos = [], array $carreras = []): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto resolver '.uniqid(),
            'codigo_proyecto' => 'RES-'.uniqid(),
        ]);

        $proyecto->facultades_centros()->sync(collect($centros)->pluck('id')->all());
        $proyecto->departamentos_academicos()->sync(collect($departamentos)->pluck('id')->all());
        $proyecto->carreras()->sync(collect($carreras)->pluck('id')->all());

        return $proyecto;
    }

    private function role(string $name = 'Rol resolver'): Role
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
            'nombre' => $nombre,
            'es_facultad' => true,
            'siglas' => 'FC'.random_int(100, 999),
            'campus_id' => $this->campus()->id,
        ]);
    }

    private function departamento(FacultadCentro $centro, string $nombre): DepartamentoAcademico
    {
        return DepartamentoAcademico::create([
            'nombre' => $nombre,
            'siglas' => 'DA'.random_int(100, 999),
            'centro_facultad_id' => $centro->id,
        ]);
    }

    private function carrera(FacultadCentro $centro, DepartamentoAcademico $departamento, string $nombre): Carrera
    {
        return Carrera::create([
            'nombre' => $nombre,
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
