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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProyectoVinculacionWorkflowStageModalStateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_limpia_solo_estado_dinamico_sin_tocar_propiedades_legacy(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $component->jefe_empleado_id = 11;
        $component->decano_empleado_id = 22;
        $component->enlace_empleado_id = 33;
        $component->firmantesPorEtapa = [1 => ['empleado_id' => 44]];
        $component->candidatosPorEtapa = [1 => [['empleado_id' => 44]]];
        $component->unidadesSinCandidatosPorEtapa = [1 => [['unidad_id' => 55]]];
        $component->mensajesFirmantesPorEtapa = [1 => 'mensaje'];
        $component->erroresFirmantesPorEtapa = ['error'];
        $component->firmantesPorEtapaListos = true;
        $component->firmantesPorEtapaBloqueado = true;
        $component->mensajeBloqueoFirmantesPorEtapa = 'bloqueado';

        $component->limpiarEstadoDinamico();

        $this->assertSame([], $component->firmantesPorEtapa);
        $this->assertSame([], $component->candidatosPorEtapa);
        $this->assertSame([], $component->unidadesSinCandidatosPorEtapa);
        $this->assertSame([], $component->mensajesFirmantesPorEtapa);
        $this->assertSame([], $component->erroresFirmantesPorEtapa);
        $this->assertFalse($component->firmantesPorEtapaListos);
        $this->assertFalse($component->firmantesPorEtapaBloqueado);
        $this->assertNull($component->mensajeBloqueoFirmantesPorEtapa);
        $this->assertSame(11, $component->jefe_empleado_id);
        $this->assertSame(22, $component->decano_empleado_id);
        $this->assertSame(33, $component->enlace_empleado_id);
    }

    public function test_prepara_etapas_activas_en_orden_separando_cargos_duplicados_y_serializando_candidatos(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $role = $this->role('Rol modal serializa');
        $centro = $this->centro('Centro modal');
        $depto = $this->departamento($centro, 'Departamento modal');
        $carrera = $this->carrera($centro, $depto, 'Carrera modal');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centro], departamentos: [$depto], carreras: [$carrera]);
        $etapaDos = $this->etapa($context, [
            'orden' => 2,
            'nombre' => 'Segunda',
            'codigo' => 'SEG',
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
        ]);
        $etapaUno = $this->etapa($context, [
            'orden' => 1,
            'nombre' => 'Primera',
            'codigo' => 'PRI',
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
        ]);
        [, $empleadoUno] = $this->usuarioEmpleado('Candidato Uno', $role, centro: $centro, departamento: $depto, carrera: $carrera);
        [, $empleadoDos] = $this->usuarioEmpleado('Candidato Dos', $role, centro: $centro, departamento: $depto, carrera: $carrera);

        $component->prepararEstado($proyecto);

        $this->assertSame([$etapaUno->id, $etapaDos->id], array_keys($component->firmantesPorEtapa));
        $this->assertSame([$context['cargo']->id, $context['cargo']->id], array_column($component->firmantesPorEtapa, 'cargo_firma_id'));
        $this->assertSame('Primera', $component->firmantesPorEtapa[$etapaUno->id]['nombre']);
        $this->assertSame('PRI', $component->firmantesPorEtapa[$etapaUno->id]['codigo']);
        $this->assertSame(1, $component->firmantesPorEtapa[$etapaUno->id]['orden']);
        $this->assertSame($role->name, $component->firmantesPorEtapa[$etapaUno->id]['rol']);
        $this->assertNull($component->firmantesPorEtapa[$etapaUno->id]['empleado_id']);
        $this->assertTrue($component->firmantesPorEtapa[$etapaUno->id]['requiere_seleccion']);
        $this->assertFalse($component->firmantesPorEtapa[$etapaUno->id]['bloqueado']);
        $this->assertCount(2, $component->candidatosPorEtapa[$etapaUno->id]);
        $candidatosEtapaUno = collect($component->candidatosPorEtapa[$etapaUno->id]);
        $candidatoDos = $candidatosEtapaUno->firstWhere('empleado_id', $empleadoDos->id);
        $this->assertNotNull($candidatoDos);
        $this->assertSame('Candidato Dos', $candidatoDos['nombre']);
        $this->assertStringContainsString('@test.local', $candidatoDos['correo']);
        $this->assertSame($centro->nombre, $candidatoDos['centro']);
        $this->assertSame($depto->nombre, $candidatoDos['departamento']);
        $this->assertSame($carrera->nombre, $candidatoDos['carrera']);
        $this->assertSame($role->name, $candidatoDos['rol_activo']);
    }

    public function test_prepara_estados_bloqueados_preseleccion_y_unidades_sin_candidatos(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $roleSinCandidato = $this->role('Rol sin candidatos');
        $roleMultiple = $this->role('Rol multiples');
        $roleFijo = $this->role('Rol fijo');
        $roleUnidad = $this->role('Rol unidad');
        $centroCon = $this->centro('Centro con candidato');
        $centroSin = $this->centro('Centro sin candidato');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centroCon, $centroSin]);
        $etapaSinCandidatos = $this->etapa($context, ['orden' => 1, 'nombre' => 'Sin candidatos', 'rol_revisor_id' => $roleSinCandidato->id]);
        $etapaMultiple = $this->etapa($context, ['orden' => 2, 'nombre' => 'Multiple', 'rol_revisor_id' => $roleMultiple->id]);
        [$responsableUser, $responsableEmpleado] = $this->usuarioEmpleado('Responsable fijo', $roleFijo);
        $etapaFija = $this->etapa($context, [
            'orden' => 3,
            'nombre' => 'Fija',
            'rol_revisor_id' => $roleFijo->id,
            'usuario_responsable_id' => $responsableUser->id,
        ]);
        $etapaPorUnidad = $this->etapa($context, [
            'orden' => 4,
            'nombre' => 'Por unidad',
            'rol_revisor_id' => $roleUnidad->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);
        $this->usuarioEmpleado('Multiple Uno', $roleMultiple);
        $this->usuarioEmpleado('Multiple Dos', $roleMultiple);
        $this->usuarioEmpleado('Unidad Uno', $roleUnidad, centro: $centroCon);

        $component->prepararEstado($proyecto);

        $this->assertTrue($component->firmantesPorEtapa[$etapaSinCandidatos->id]['bloqueado']);
        $this->assertSame('No existen candidatos elegibles para esta etapa.', $component->firmantesPorEtapa[$etapaSinCandidatos->id]['mensaje']);
        $this->assertNull($component->firmantesPorEtapa[$etapaMultiple->id]['empleado_id']);
        $this->assertTrue($component->firmantesPorEtapa[$etapaMultiple->id]['requiere_seleccion']);
        $this->assertSame($responsableEmpleado->id, $component->firmantesPorEtapa[$etapaFija->id]['empleado_id']);
        $this->assertFalse($component->firmantesPorEtapa[$etapaFija->id]['requiere_seleccion']);
        $this->assertTrue($component->firmantesPorEtapa[$etapaPorUnidad->id]['bloqueado']);
        $this->assertStringContainsString('revisor por unidad', $component->firmantesPorEtapa[$etapaPorUnidad->id]['mensaje']);
        $this->assertSame($centroSin->id, $component->unidadesSinCandidatosPorEtapa[$etapaPorUnidad->id][0]['unidad_id']);
        $this->assertSame($centroSin->nombre, $component->unidadesSinCandidatosPorEtapa[$etapaPorUnidad->id][0]['unidad_nombre']);
        $this->assertTrue($component->firmantesPorEtapaBloqueado);
        $this->assertFalse($component->firmantesPorEtapaListos);
    }

    public function test_error_de_configuracion_queda_registrado_y_bloquea_estado(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $proyecto = $this->proyecto($context['flujo']);

        $component->prepararEstado($proyecto);

        $this->assertNotEmpty($component->erroresFirmantesPorEtapa);
        $this->assertStringContainsString('No hay etapas activas configuradas', $component->erroresFirmantesPorEtapa[0]);
        $this->assertTrue($component->firmantesPorEtapaBloqueado);
        $this->assertFalse($component->firmantesPorEtapaListos);
        $this->assertStringContainsString('No se puede preparar', $component->mensajeBloqueoFirmantesPorEtapa);
    }

    public function test_recalcula_listo_cuando_todas_las_etapas_unicas_tienen_seleccion(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $role = $this->role('Rol listo');
        $proyecto = $this->proyecto($context['flujo']);
        $etapaUno = $this->etapa($context, ['orden' => 1, 'nombre' => 'Uno', 'rol_revisor_id' => $role->id]);
        $etapaDos = $this->etapa($context, ['orden' => 2, 'nombre' => 'Dos', 'rol_revisor_id' => $role->id]);
        [, $empleadoUno] = $this->usuarioEmpleado('Listo Uno', $role);
        [, $empleadoDos] = $this->usuarioEmpleado('Listo Dos', $role);

        $component->prepararEstado($proyecto);

        $this->assertFalse($component->firmantesPorEtapaListos);

        $component->seleccionarFirmante($proyecto, $etapaUno->id, $empleadoUno->id);
        $component->seleccionarFirmante($proyecto, $etapaDos->id, $empleadoDos->id);

        $this->assertTrue($component->firmantesPorEtapaListos);
        $this->assertFalse($component->firmantesPorEtapaBloqueado);
        $this->assertNull($component->mensajeBloqueoFirmantesPorEtapa);
    }

    public function test_seleccionar_firmante_acepta_elegible_rechaza_no_elegible_y_no_modifica_legacy(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $component->jefe_empleado_id = 101;
        $component->decano_empleado_id = 202;
        $component->enlace_empleado_id = 303;
        $context = $this->contexto();
        $role = $this->role('Rol seleccion');
        $otroRole = $this->role('Rol no elegible');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);
        [, $empleadoElegible] = $this->usuarioEmpleado('Seleccion elegible', $role);
        [, $empleadoNoElegible] = $this->usuarioEmpleado('Seleccion no elegible', $otroRole);

        $component->prepararEstado($proyecto);
        $component->seleccionarFirmante($proyecto, $etapa->id, $empleadoElegible->id);

        $this->assertSame($empleadoElegible->id, $component->firmantesPorEtapa[$etapa->id]['empleado_id']);
        $this->assertRuntime(
            fn () => $component->seleccionarFirmante($proyecto, $etapa->id, $empleadoNoElegible->id),
            'El empleado seleccionado no es elegible'
        );
        $this->assertNull($component->firmantesPorEtapa[$etapa->id]['empleado_id']);
        $this->assertSame(101, $component->jefe_empleado_id);
        $this->assertSame(202, $component->decano_empleado_id);
        $this->assertSame(303, $component->enlace_empleado_id);
    }

    public function test_asignaciones_normalizadas_retornan_etapa_empleado_y_fallan_si_falta_o_sobra_etapa(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $role = $this->role('Rol normaliza');
        $proyecto = $this->proyecto($context['flujo']);
        $etapaUno = $this->etapa($context, ['orden' => 1, 'nombre' => 'Uno', 'rol_revisor_id' => $role->id]);
        $etapaDos = $this->etapa($context, ['orden' => 2, 'nombre' => 'Dos', 'rol_revisor_id' => $role->id]);
        [, $empleadoUno] = $this->usuarioEmpleado('Normaliza Uno', $role);
        [, $empleadoDos] = $this->usuarioEmpleado('Normaliza Dos', $role);

        $component->prepararEstado($proyecto);
        $component->seleccionarFirmante($proyecto, $etapaUno->id, $empleadoUno->id);
        $component->seleccionarFirmante($proyecto, $etapaDos->id, $empleadoDos->id);

        $this->assertSame([
            $etapaUno->id => $empleadoUno->id,
            $etapaDos->id => $empleadoDos->id,
        ], $component->asignacionesNormalizadas($proyecto));

        unset($component->firmantesPorEtapa[$etapaDos->id]);
        $this->assertRuntime(
            fn () => $component->asignacionesNormalizadas($proyecto),
            'No se indic'
        );

        $component->prepararEstado($proyecto);
        $component->seleccionarFirmante($proyecto, $etapaUno->id, $empleadoUno->id);
        $component->seleccionarFirmante($proyecto, $etapaDos->id, $empleadoDos->id);
        $component->firmantesPorEtapa[999999] = $component->firmantesPorEtapa[$etapaUno->id];
        $component->firmantesPorEtapa[999999]['etapa_id'] = 999999;
        $component->firmantesPorEtapa[999999]['empleado_id'] = $empleadoUno->id;

        $this->assertRuntime(
            fn () => $component->asignacionesNormalizadas($proyecto),
            'La etapa indicada no pertenece'
        );
    }

    public function test_asignaciones_fallan_si_falta_seleccion_o_hay_por_cada_unidad(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $role = $this->role('Rol falla asignacion');
        $centro = $this->centro('Centro asignacion');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centro]);
        $etapa = $this->etapa($context, ['nombre' => 'Falta seleccion', 'rol_revisor_id' => $role->id]);
        $this->usuarioEmpleado('Disponible asignacion', $role);

        $component->prepararEstado($proyecto);

        $this->assertRuntime(
            fn () => $component->asignacionesNormalizadas($proyecto),
            'Debe seleccionar un firmante para la etapa "Falta seleccion".'
        );

        $etapa->update([
            'nombre' => 'Por unidad asignacion',
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);
        $component->prepararEstado($proyecto->fresh());

        $this->assertRuntime(
            fn () => $component->asignacionesNormalizadas($proyecto->fresh()),
            'revisor por unidad'
        );
    }

    public function test_metodos_dinamicos_no_crean_firmas_estados_no_modifican_proyecto_ni_llaman_flujo_legacy(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $context = $this->contexto();
        $role = $this->role('Rol sin efectos modal');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['rol_revisor_id' => $role->id]);
        [, $empleado] = $this->usuarioEmpleado('Sin efectos modal', $role);
        $snapshotProyecto = $proyecto->fresh()->getAttributes();
        $firmas = FirmaProyecto::count();
        $estados = DB::table('estado_proyecto')->count();

        $component->prepararEstado($proyecto);
        $component->seleccionarFirmante($proyecto, $etapa->id, $empleado->id);
        $component->asignacionesNormalizadas($proyecto);

        $this->assertSame($snapshotProyecto, $proyecto->fresh()->getAttributes());
        $this->assertSame($firmas, FirmaProyecto::count());
        $this->assertSame($estados, DB::table('estado_proyecto')->count());
        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertFalse($component->sincronizarFirmasDelFlujoLlamado);
        $this->assertFalse($component->sincronizarFirmasDeEtapasDelFlujoLlamado);
    }

    public function test_metodos_dinamicos_permanecen_desconectados_del_modal_actual(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalStateComponent;
        $component->firmaSearch = 'busqueda legacy';

        $component->limpiarEstadoDinamico();

        $this->assertSame('busqueda legacy', $component->firmaSearch);
        $this->assertFalse(property_exists($component, 'showFirmasPorEtapaModal'));
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
            'codigo' => 'FLUJO_MODAL_'.uniqid(),
            'nombre' => 'Flujo modal',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado modal '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo modal '.uniqid()]);
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
            'nombre' => 'Etapa modal',
            'cargo_firma_id' => $context['cargo']->id,
            'activo' => true,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
        ], $attributes));
    }

    private function proyecto(
        ?FlujoAprobacion $flujo = null,
        array $centros = [],
        array $departamentos = [],
        array $carreras = []
    ): Proyecto {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto modal '.uniqid(),
            'codigo_proyecto' => 'MDL-'.uniqid(),
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
}

class CreateProyectoVinculacionWorkflowStageModalStateComponent extends CreateProyectoVinculacion
{
    public bool $saveFirmasLlamado = false;
    public bool $sincronizarFirmasDelFlujoLlamado = false;
    public bool $sincronizarFirmasDeEtapasDelFlujoLlamado = false;

    public function limpiarEstadoDinamico(): void
    {
        $this->limpiarEstadoFirmantesPorEtapa();
    }

    public function prepararEstado(Proyecto $proyecto): void
    {
        $this->prepararEstadoFirmantesPorEtapa($proyecto);
    }

    public function seleccionarFirmante(Proyecto $proyecto, int $etapaId, int $empleadoId): void
    {
        $this->seleccionarFirmantePorEtapa($proyecto, $etapaId, $empleadoId);
    }

    public function asignacionesNormalizadas(Proyecto $proyecto): array
    {
        return $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);
    }

    protected function saveFirmas(Proyecto $record): void
    {
        $this->saveFirmasLlamado = true;
    }

    protected function sincronizarFirmasDelFlujo(): void
    {
        $this->sincronizarFirmasDelFlujoLlamado = true;
    }

    protected function sincronizarFirmasDeEtapasDelFlujo(): void
    {
        $this->sincronizarFirmasDeEtapasDelFlujoLlamado = true;
    }
}
