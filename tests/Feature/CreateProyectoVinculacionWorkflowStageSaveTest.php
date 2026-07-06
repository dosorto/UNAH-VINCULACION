<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProyectoVinculacionWorkflowStageSaveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_falla_si_estado_dinamico_no_esta_listo(): void
    {
        $context = $this->contexto();
        $component = $this->componente();

        $this->assertFallaSinCrearDatos($component, $context['proyecto']);

        $component->preparar($context['proyecto']);
        $component->firmantesPorEtapaListos = false;
        $this->assertFallaSinCrearDatos($component, $context['proyecto']);

        $component->preparar($context['proyecto']);
        $component->firmantesPorEtapaBloqueado = true;
        $this->assertFallaSinCrearDatos($component, $context['proyecto']);

        $component->preparar($context['proyecto']);
        $component->erroresFirmantesPorEtapa[] = 'Error controlado';
        $this->assertFallaSinCrearDatos($component, $context['proyecto']);

        $component->preparar($context['proyecto']);
        $component->unidadesSinCandidatosPorEtapa[$context['etapas'][0]->id] = [['unidad_id' => 1]];
        $this->assertFallaSinCrearDatos($component, $context['proyecto']);
    }

    public function test_falla_con_por_cada_unidad_faltante_o_empleado_no_elegible(): void
    {
        $contextPorUnidad = $this->contexto(1, attributesEtapa: [
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);
        $component = $this->componente();
        $component->preparar($contextPorUnidad['proyecto']);

        $this->assertFallaSinCrearDatos($component, $contextPorUnidad['proyecto']);

        $context = $this->contexto();
        $component = $this->componente();
        $component->preparar($context['proyecto']);
        $component->firmantesPorEtapa[$context['etapas'][0]->id]['empleado_id'] = null;
        $component->firmantesPorEtapaListos = true;

        $this->assertFallaSinCrearDatos($component, $context['proyecto']);

        [, $noElegible] = $this->usuarioEmpleado('No elegible');
        $component->preparar($context['proyecto']);
        $component->firmantesPorEtapa[$context['etapas'][0]->id]['empleado_id'] = $noElegible->id;
        $component->firmantesPorEtapaListos = true;

        $this->assertFallaSinCrearDatos($component, $context['proyecto']);
    }

    public function test_crea_firmas_por_etapa_con_snapshots_estado_inicial_y_sin_legacy(): void
    {
        $context = $this->contexto(3, mismoCargo: true, conInactiva: true);
        $component = $this->componente();
        $component->jefe_empleado_id = 11;
        $component->decano_empleado_id = 22;
        $component->enlace_empleado_id = 33;
        $component->preparar($context['proyecto']);

        foreach ($context['etapas'] as $index => $etapa) {
            $component->seleccionar($context['proyecto'], $etapa->id, $context['empleados'][$index]->id);
        }

        $firmasAntes = FirmaProyecto::count();
        $estadoInicialCount = $context['proyecto']->estado_proyecto()->count();

        $firmas = $component->guardarPorEtapa($context['proyecto']);

        $this->assertCount(3, $firmas);
        $this->assertSame($firmasAntes + 3, FirmaProyecto::count());
        $this->assertSame($estadoInicialCount + 1, $context['proyecto']->estado_proyecto()->count());
        $this->assertSame([], $context['proyecto']->firma_proyecto()->whereNull('flujo_aprobacion_etapa_id')->pluck('id')->all());
        $this->assertSame(11, $component->jefe_empleado_id);
        $this->assertSame(22, $component->decano_empleado_id);
        $this->assertSame(33, $component->enlace_empleado_id);

        foreach ($firmas as $index => $firma) {
            $etapa = $context['etapas'][$index];
            $this->assertSame('Pendiente', $firma->estado_revision);
            $this->assertSame(1, $firma->revision_ciclo);
            $this->assertSame($context['flujo']->id, $firma->flujo_aprobacion_id);
            $this->assertSame($etapa->id, $firma->flujo_aprobacion_etapa_id);
            $this->assertSame($etapa->orden, $firma->orden_revision);
            $this->assertSame($etapa->codigo, $firma->etapa_codigo);
            $this->assertSame($etapa->nombre, $firma->etapa_nombre);
            $this->assertSame($context['roles'][$index]->name, $firma->rol_requerido);
            $this->assertSame($context['empleados'][$index]->id, $firma->empleado_id);
            $this->assertNull($firma->firma_id);
            $this->assertNull($firma->sello_id);
            $this->assertNull($firma->fecha_firma);
        }

        $this->assertSame($context['etapas'][0]->cargo_firma_id, $context['etapas'][1]->cargo_firma_id);
        $this->assertNotSame($firmas[0]->flujo_aprobacion_etapa_id, $firmas[1]->flujo_aprobacion_etapa_id);
        $this->assertFalse($context['proyecto']->firma_proyecto()->where('flujo_aprobacion_etapa_id', $context['etapaInactiva']->id)->exists());
        $this->assertSame($context['cargos'][0]->tipo_estado_id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertSame('Proyecto enviado a revision por flujo de etapas.', $context['proyecto']->estado->comentario);
        $this->assertSame($firmas[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 1)?->id);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmas[0]->fresh()));
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmas[1]->fresh()));
        $this->assertFalse($context['proyecto']->firmasDeEtapasCompletadas($context['flujo']->id, 1));
    }

    public function test_primera_firma_aparece_en_proyectos_por_firmar_y_otro_empleado_no_la_ve(): void
    {
        $context = $this->contexto(2);
        $component = $this->componente();
        $component->preparar($context['proyecto']);

        foreach ($context['etapas'] as $index => $etapa) {
            $component->seleccionar($context['proyecto'], $etapa->id, $context['empleados'][$index]->id);
        }

        $firmas = $component->guardarPorEtapa($context['proyecto']);
        $primerUsuario = $context['empleados'][0]->user->fresh();
        [$otroUsuario] = $this->usuarioEmpleado($context['roles'][0]->name, $context['roles'][0]);

        $this->assertContains($firmas[0]->id, $this->firmasDisponiblesIds($primerUsuario));
        $this->assertNotContains($firmas[0]->id, $this->firmasDisponiblesIds($otroUsuario));
    }

    public function test_repetir_falla_por_firmas_existentes_y_legacy_pendiente_bloquea(): void
    {
        $context = $this->contexto();
        $component = $this->componente();
        $this->prepararYSeleccionar($component, $context);
        $component->guardarPorEtapa($context['proyecto']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ya existen firmas por etapa para este proyecto.');
        $component->guardarPorEtapa($context['proyecto']);
    }

    public function test_legacy_pendiente_bloquea_antes_de_crear_firmas_por_etapa(): void
    {
        $context = $this->contexto();
        $component = $this->componente();
        $this->prepararYSeleccionar($component, $context);
        $context['proyecto']->firma_proyecto()->create([
            'empleado_id' => $context['empleados'][0]->id,
            'cargo_firma_id' => $context['cargos'][0]->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-legacy',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ya existen firmantes manuales para este envío y no se puede iniciar la revisión por etapas.');

        $component->guardarPorEtapa($context['proyecto']);
    }

    public function test_si_falla_creacion_de_estado_revierte_firmas(): void
    {
        $context = $this->contexto(2, estadoPrimeraFirmaNull: true);
        $component = $this->componente();
        $this->prepararYSeleccionar($component, $context);
        $firmasAntes = FirmaProyecto::count();
        $estadosAntes = $context['proyecto']->estado_proyecto()->count();

        try {
            $component->guardarPorEtapa($context['proyecto']);
            $this->fail('El envio por etapas debio fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('No se pudo preparar el envio por etapas de forma segura.', $exception->getMessage());
            $this->assertSame($firmasAntes, FirmaProyecto::count());
            $this->assertSame($estadosAntes, $context['proyecto']->estado_proyecto()->count());
        }
    }

    public function test_si_falla_validacion_posterior_revierte_estado_y_firmas(): void
    {
        $context = $this->contexto(2);
        $component = $this->componente(fallarValidacionPosterior: true);
        $this->prepararYSeleccionar($component, $context);
        $firmasAntes = FirmaProyecto::count();
        $estadosAntes = $context['proyecto']->estado_proyecto()->count();

        try {
            $component->guardarPorEtapa($context['proyecto']);
            $this->fail('El envio por etapas debio fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('No se pudo preparar el envio por etapas de forma segura.', $exception->getMessage());
            $this->assertSame($firmasAntes, FirmaProyecto::count());
            $this->assertSame($estadosAntes, $context['proyecto']->estado_proyecto()->count());
        }
    }

    public function test_permanece_desconectado_del_flujo_productivo_y_de_la_vista(): void
    {
        $source = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));
        $view = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('protected function guardarFirmasPorEtapaDesdeSeleccionDinamica(', $source);
        $this->assertStringContainsString('guardarFirmasPorEtapaDesdeSeleccionDinamica($record)', $this->methodBody($source, 'create'));
        $this->assertStringNotContainsString('sincronizarFirmasDeEtapasDelFlujo', $this->methodBody($source, 'create'));
        $this->assertStringNotContainsString('guardarFirmasPorEtapaDesdeSeleccionDinamica', $this->methodBody($source, 'saveFirmas'));
        $this->assertStringNotContainsString('guardarFirmasPorEtapaDesdeSeleccionDinamica', $this->methodBody($source, 'ensureRecord'));
        $this->assertStringNotContainsString('Notification::make', $this->methodBody($source, 'guardarFirmasPorEtapaDesdeSeleccionDinamica'));
        $this->assertStringNotContainsString('guardarFirmasPorEtapaDesdeSeleccionDinamica', $view);
        $this->assertStringContainsString('wire:click="create"', $view);
    }

    private function componente(bool $fallarValidacionPosterior = false): CreateProyectoVinculacionWorkflowStageSaveComponent
    {
        [$user] = $this->usuarioEmpleado('Usuario envia');
        $this->actingAs($user);

        $component = new CreateProyectoVinculacionWorkflowStageSaveComponent;
        $component->fallarValidacionPosterior = $fallarValidacionPosterior;

        return $component;
    }

    private function prepararYSeleccionar(CreateProyectoVinculacionWorkflowStageSaveComponent $component, array $context): void
    {
        $component->preparar($context['proyecto']);

        foreach ($context['etapas'] as $index => $etapa) {
            $component->seleccionar($context['proyecto'], $etapa->id, $context['empleados'][$index]->id);
        }
    }

    private function assertFallaSinCrearDatos(CreateProyectoVinculacionWorkflowStageSaveComponent $component, Proyecto $proyecto): void
    {
        $firmas = FirmaProyecto::count();
        $estados = $proyecto->estado_proyecto()->count();

        try {
            $component->guardarPorEtapa($proyecto);
            $this->fail('El envio por etapas debio fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Los firmantes por etapa no estan listos para enviar el proyecto a revision.', $exception->getMessage());
            $this->assertSame($firmas, FirmaProyecto::count());
            $this->assertSame($estados, $proyecto->estado_proyecto()->count());
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

    private function contexto(
        int $cantidadEtapas = 1,
        bool $mismoCargo = false,
        bool $conInactiva = false,
        bool $estadoPrimeraFirmaNull = false,
        array $attributesEtapa = []
    ): array {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_SAVE_'.uniqid(),
            'nombre' => 'Flujo save',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto save '.uniqid(),
            'codigo_proyecto' => 'SAVE-'.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $this->usuarioEmpleado('Empleado estado inicial')[1]->id,
            'tipo_estado_id' => $this->tipoEstado('Borrador save '.uniqid())->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        $etapas = [];
        $empleados = [];
        $roles = [];
        $cargos = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estadoId = $estadoPrimeraFirmaNull && $orden === 1
                ? null
                : $this->tipoEstado('Estado save '.$orden.' '.uniqid())->id;
            $cargoCompartido = $mismoCargo && $cargoCompartido
                ? $cargoCompartido
                : $this->cargoFirma($estadoId, 'Cargo save '.$orden.' '.uniqid());
            $role = $this->role('Rol save '.$orden);
            [, $empleado] = $this->usuarioEmpleado('Empleado etapa '.$orden, $role);
            $etapa = $flujo->etapas()->create(array_merge([
                'orden' => $orden,
                'codigo' => 'SAVE_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa save '.$orden,
                'cargo_firma_id' => $cargoCompartido->id,
                'rol_revisor_id' => $role->id,
                'activo' => true,
                'aplica_proyecto_inscripcion' => true,
                'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
                'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
            ], $attributesEtapa));

            $etapas[] = $etapa;
            $empleados[] = $empleado;
            $roles[] = $role;
            $cargos[] = $cargoCompartido;
        }

        $etapaInactiva = null;
        if ($conInactiva) {
            $etapaInactiva = $flujo->etapas()->create([
                'orden' => 99,
                'codigo' => 'SAVE_INACTIVA_'.uniqid(),
                'nombre' => 'Etapa inactiva',
                'cargo_firma_id' => $cargos[0]->id,
                'rol_revisor_id' => $roles[0]->id,
                'activo' => false,
                'aplica_proyecto_inscripcion' => true,
                'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
                'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
            ]);
        }

        return compact('flujo', 'proyecto', 'etapas', 'empleados', 'roles', 'cargos', 'etapaInactiva');
    }

    private function usuarioEmpleado(string $name, ?Role $role = null): array
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@test.local',
            'active_role_id' => $role?->id,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        $empleado = Empleado::create([
            'nombre_completo' => $name,
            'numero_empleado' => 'SAVE-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);

        return [$user->fresh('roles', 'empleado'), $empleado->fresh('user.roles')];
    }

    private function role(string $name): Role
    {
        return Role::create([
            'name' => $name.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function tipoEstado(string $nombre): TipoEstado
    {
        return TipoEstado::create(['nombre' => $nombre]);
    }

    private function cargoFirma(?int $tipoEstadoId, string $nombre): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create(['nombre' => $nombre]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }

    private function methodBody(string $source, string $method): string
    {
        $pattern = '/function\s+'.preg_quote($method, '/').'\s*\([^)]*\)\s*(?::\s*[^{]+)?\{/m';

        if (! preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE)) {
            $this->fail("No se encontro el metodo {$method}.");
        }

        $start = $match[0][1];
        $brace = strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);

        for ($i = $brace; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $start, $i - $start + 1);
                }
            }
        }

        $this->fail("No se pudo extraer el metodo {$method}.");
    }
}

class CreateProyectoVinculacionWorkflowStageSaveComponent extends CreateProyectoVinculacion
{
    public bool $fallarValidacionPosterior = false;

    public function preparar(Proyecto $proyecto): void
    {
        $this->prepararEstadoFirmantesPorEtapa($proyecto);
    }

    public function seleccionar(Proyecto $proyecto, int $etapaId, int $empleadoId): void
    {
        $this->seleccionarFirmantePorEtapa($proyecto, $etapaId, $empleadoId);
    }

    public function guardarPorEtapa(Proyecto $proyecto): Collection
    {
        return $this->guardarFirmasPorEtapaDesdeSeleccionDinamica($proyecto);
    }

    protected function saveFirmas(Proyecto $record): void
    {
        throw new \RuntimeException('saveFirmas no debe ser llamado en 8B.');
    }

    protected function validarResultadoFirmasPorEtapa(Proyecto $proyecto, Collection $firmas): void
    {
        if ($this->fallarValidacionPosterior) {
            throw new \RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
        }

        parent::validarResultadoFirmasPorEtapa($proyecto, $firmas);
    }
}
