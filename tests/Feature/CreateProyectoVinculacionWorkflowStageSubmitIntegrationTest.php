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
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProyectoVinculacionWorkflowStageSubmitIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_validar_no_activa_automaticamente_y_activar_exige_estado_listo(): void
    {
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $component->preparar($context['proyecto']);

        $component->validarFirmantesPorEtapaParaVista();
        $this->assertFalse($component->usarFirmantesPorEtapaParaEnvio);
        $this->assertFalse($component->firmantesPorEtapaListos);

        $component->activarFirmantesPorEtapaParaEnvio();
        $this->assertFalse($component->usarFirmantesPorEtapaParaEnvio);
        $this->assertSame('Los firmantes por etapa no estan listos para enviar el proyecto a revision.', $component->mensajeBloqueoFirmantesPorEtapa);

        $component->seleccionar($context['proyecto'], $context['etapas'][0]->id, $context['empleados'][0]->id);
        $component->validarFirmantesPorEtapaParaVista();
        $component->activarFirmantesPorEtapaParaEnvio();

        $this->assertTrue($component->usarFirmantesPorEtapaParaEnvio);
        $this->assertSame('Firmantes por etapa activados para este envio.', $component->mensajeFirmantesPorEtapaVista);
    }

    public function test_activar_falla_con_etapa_bloqueada_y_desactivar_vuelve_a_legacy_sin_borrar_selecciones(): void
    {
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $component->preparar($context['proyecto']);
        $component->firmantesPorEtapa[$context['etapas'][0]->id]['bloqueado'] = true;
        $component->firmantesPorEtapaBloqueado = true;

        $component->activarFirmantesPorEtapaParaEnvio();
        $this->assertFalse($component->usarFirmantesPorEtapaParaEnvio);

        $component->firmantesPorEtapa[$context['etapas'][0]->id]['bloqueado'] = false;
        $component->firmantesPorEtapaBloqueado = false;
        $component->seleccionar($context['proyecto'], $context['etapas'][0]->id, $context['empleados'][0]->id);
        $component->activarFirmantesPorEtapaParaEnvio();
        $component->desactivarFirmantesPorEtapaParaEnvio();

        $this->assertFalse($component->usarFirmantesPorEtapaParaEnvio);
        $this->assertSame($context['empleados'][0]->id, $component->firmantesPorEtapa[$context['etapas'][0]->id]['empleado_id']);
    }

    public function test_vista_muestra_modos_y_conserva_boton_create(): void
    {
        $view = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('El proyecto se enviará usando el flujo por etapas.', $view);
        $this->assertStringContainsString('El proyecto se enviará usando el flujo legacy actual.', $view);
        $this->assertStringContainsString('Activar firmantes por etapa para este envío', $view);
        $this->assertStringContainsString('Usar envío legacy', $view);
        $this->assertStringContainsString('Al activar el flujo por etapas, no se crearán las firmas legacy de jefe, decano/enlace.', $view);
        $this->assertStringContainsString('wire:click="create"', $view);
    }

    public function test_create_sin_activar_conserva_flujo_legacy(): void
    {
        Mail::fake();
        $context = $this->contextoLegacy();
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $component->jefe_empleado_id = $context['firmantes'][0]->id;
        $component->decano_empleado_id = $context['firmantes'][1]->id;
        $component->enlace_empleado_id = $context['firmantes'][2]->id;

        $component->create();

        $this->assertTrue($component->autoGuardadoLlamado);
        $this->assertTrue($component->saveFirmasLlamado);
        $this->assertFalse($component->guardarPorEtapaLlamado);
        $this->assertGreaterThanOrEqual(4, $context['proyecto']->firma_proyecto()->whereNull('flujo_aprobacion_etapa_id')->count());
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->count());
        $this->assertSame('Proyecto enviado para firma', $context['proyecto']->estado->comentario);
    }

    public function test_create_por_etapa_no_ejecuta_legacy_y_crea_firmas_por_etapa(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas(2);
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $component->jefe_empleado_id = null;
        $component->decano_empleado_id = null;
        $component->enlace_empleado_id = null;
        $this->prepararSeleccionarYActivar($component, $context);

        $component->create();

        $firmas = $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 1);
        $this->assertTrue($component->guardarPorEtapaLlamado);
        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertCount(2, $firmas);
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->whereNull('flujo_aprobacion_etapa_id')->count());
        $this->assertSame($context['estados'][0]->id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertSame($firmas[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 1)?->id);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmas[0]));
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmas[1]));
    }

    public function test_create_por_etapa_deja_primera_firma_visible_y_aprobable_desde_bandeja(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas(2);
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);

        $component->create();

        $firmas = $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 1);
        $this->assertContains($firmas[0]->id, $this->firmasDisponiblesIds($context['empleados'][0]->user->fresh()));

        Livewire::actingAs($context['empleados'][0]->user->fresh())
            ->test(ProyectosPorFirmar::class, ['docente' => $context['empleados'][0]])
            ->call('aprobar', $firmas[0]->id)
            ->assertOk();

        $this->assertSame('Aprobado', $firmas[0]->refresh()->estado_revision);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmas[1]->refresh()));
    }

    public function test_create_por_etapa_permite_rechazar_desde_bandeja(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas(2);
        $this->tipoEstado('Subsanacion');
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);

        $component->create();

        $firmas = $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 1);
        Livewire::actingAs($context['empleados'][0]->user->fresh())
            ->test(ProyectosPorFirmar::class, ['docente' => $context['empleados'][0]])
            ->set('rechazarId', $firmas[0]->id)
            ->set('rechazarComentario', 'Debe corregir')
            ->call('rechazar')
            ->assertOk();

        $this->assertSame('Rechazado', $firmas[0]->refresh()->estado_revision);
        $this->assertSame('Subsanacion', $context['proyecto']->estado->tipoestado->nombre);
    }

    public function test_create_por_etapa_con_estado_invalido_no_cae_a_legacy(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);
        $component->firmantesPorEtapa[$context['etapas'][0]->id]['empleado_id'] = null;
        $component->jefe_empleado_id = $context['empleados'][0]->id;

        $component->create();

        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertFalse($component->guardarPorEtapaLlamado);
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->count());
    }

    public function test_create_por_etapa_si_guardado_falla_no_cae_a_legacy(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto'], fallarGuardadoPorEtapa: true);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);
        $component->jefe_empleado_id = $context['empleados'][0]->id;

        $component->create();

        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertTrue($component->guardarPorEtapaLlamado);
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->count());
    }

    public function test_create_por_etapa_falla_con_firmas_existentes_sin_crear_legacy(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);
        $context['proyecto']->sincronizarFirmasDeEtapasDelFlujo([
            $context['etapas'][0]->id => $context['empleados'][0]->id,
        ], Proyecto::FLUJO_INSCRIPCION, null, 1);

        $component->create();

        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertTrue($component->guardarPorEtapaLlamado);
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->whereNull('flujo_aprobacion_etapa_id')->count());
    }

    public function test_create_por_etapa_falla_con_legacy_conflictiva_sin_crear_etapas(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $this->formularioValido($component);
        $this->prepararSeleccionarYActivar($component, $context);
        $context['proyecto']->firma_proyecto()->create([
            'empleado_id' => $context['empleados'][0]->id,
            'cargo_firma_id' => $context['cargos'][0]->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-legacy-conflicto',
        ]);

        $component->create();

        $this->assertFalse($component->saveFirmasLlamado);
        $this->assertTrue($component->guardarPorEtapaLlamado);
        $this->assertSame(0, $context['proyecto']->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->count());
    }

    public function test_create_por_etapa_conserva_validaciones_generales(): void
    {
        Mail::fake();
        $context = $this->contextoEtapas();
        $component = $this->componente($context['proyecto']);
        $this->prepararSeleccionarYActivar($component, $context);
        $component->resumen = '';

        try {
            $component->create();
            $this->fail('La validacion general debio fallar.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertFalse($component->saveFirmasLlamado);
            $this->assertFalse($component->guardarPorEtapaLlamado);
            $this->assertSame(0, $context['proyecto']->firma_proyecto()->count());
        }
    }

    public function test_aislamiento_de_archivos_y_create_no_llama_sync_por_etapa_directo(): void
    {
        $source = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));
        $create = $this->methodBody($source, 'create');

        $this->assertStringContainsString('$this->guardarFirmasPorEtapaDesdeSeleccionDinamica($record);', $create);
        $this->assertStringContainsString('$this->saveFirmas($record);', $create);
        $this->assertStringContainsString('$record->sincronizarFirmasDelFlujo();', $create);
        $this->assertStringNotContainsString('sincronizarFirmasDeEtapasDelFlujo', $create);
        $this->assertFileDoesNotExist(database_path('migrations/9999_99_99_999999_create_workflow_stage_submit.php'));
        $this->assertFileExists(app_path('Livewire/Docente/Proyectos/ProyectosPorFirmar.php'));
        $this->assertFileExists(app_path('Livewire/Docente/Proyectos/HistorialProyecto.php'));
        $this->assertFileExists(app_path('Models/Proyecto/Proyecto.php'));
    }

    private function componente(Proyecto $proyecto, bool $fallarGuardadoPorEtapa = false): CreateProyectoVinculacionWorkflowStageSubmitComponent
    {
        [$user] = $this->usuarioEmpleado('Usuario submit');
        $this->actingAs($user);

        $component = new CreateProyectoVinculacionWorkflowStageSubmitComponent;
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;
        $component->fallarGuardadoPorEtapa = $fallarGuardadoPorEtapa;

        return $component;
    }

    private function formularioValido(CreateProyectoVinculacion $component): void
    {
        foreach (['resumen', 'descripcion_participantes', 'definicion_problema', 'alineamiento_reforma', 'impacto_deseado', 'metodologia', 'bibliografia'] as $campo) {
            $component->{$campo} = 'Texto valido';
        }

        $component->objetivo_general = 'Objetivo general';
        $component->objetivosEspecificos = [[
            'descripcion' => 'Objetivo especifico',
            'resultados' => [[
                'nombre_resultado' => 'Resultado',
                'nombre_indicador' => 'Indicador',
                'nombre_medio_verificacion' => 'Medio',
                'plazo' => 'corto_plazo',
            ]],
        ]];
    }

    private function prepararSeleccionarYActivar(CreateProyectoVinculacionWorkflowStageSubmitComponent $component, array $context): void
    {
        $component->preparar($context['proyecto']);

        foreach ($context['etapas'] as $index => $etapa) {
            $component->seleccionar($context['proyecto'], $etapa->id, $context['empleados'][$index]->id);
        }

        $component->validarFirmantesPorEtapaParaVista();
        $component->activarFirmantesPorEtapaParaEnvio();
        $this->assertTrue($component->usarFirmantesPorEtapaParaEnvio);
    }

    private function firmasDisponiblesIds(User $user): array
    {
        $this->actingAs($user);
        $component = new ProyectosPorFirmar;
        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'firmasDisponiblesQuery');
        $method->setAccessible(true);

        return $method->invoke($component)->pluck('firma_proyecto.id')->all();
    }

    private function contextoEtapas(int $cantidadEtapas = 1): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_SUBMIT_'.uniqid(),
            'nombre' => 'Flujo submit',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $proyecto = $this->proyecto($flujo);
        $etapas = [];
        $empleados = [];
        $roles = [];
        $estados = [];
        $cargos = [];

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->tipoEstado('Estado submit '.$orden.' '.uniqid());
            $cargo = $this->cargoFirma($estado->id, 'Cargo submit '.$orden.' '.uniqid());
            $role = $this->role('Rol submit '.$orden);
            [, $empleado] = $this->usuarioEmpleado('Empleado submit '.$orden, $role);
            $etapas[] = $flujo->etapas()->create([
                'orden' => $orden,
                'codigo' => 'SUBMIT_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa submit '.$orden,
                'cargo_firma_id' => $cargo->id,
                'rol_revisor_id' => $role->id,
                'activo' => true,
                'aplica_proyecto_inscripcion' => true,
                'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
                'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
            ]);
            $empleados[] = $empleado;
            $roles[] = $role;
            $estados[] = $estado;
            $cargos[] = $cargo;
        }

        return compact('flujo', 'proyecto', 'etapas', 'empleados', 'roles', 'estados', 'cargos');
    }

    private function contextoLegacy(): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_LEGACY_'.uniqid(),
            'nombre' => 'Flujo legacy',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $proyecto = $this->proyecto($flujo);
        $firmantes = [];

        foreach (['Jefe Departamento', 'Director centro', 'Enlace Vinculacion', 'Coordinador Proyecto'] as $nombre) {
            $this->cargoFirma($this->tipoEstado('Estado '.$nombre.' '.uniqid())->id, $nombre);
            [, $firmantes[]] = $this->usuarioEmpleado('Empleado '.$nombre);
        }

        $roleSync = $this->role('Rol sync legacy');
        [, $empleadoSync] = $this->usuarioEmpleado('Empleado sync legacy', $roleSync);
        $cargoSync = $this->cargoFirma($this->tipoEstado('Estado sync legacy '.uniqid())->id, 'Cargo sync legacy');
        $flujo->etapas()->create([
            'orden' => 1,
            'codigo' => 'SYNC_LEGACY_'.uniqid(),
            'nombre' => 'Etapa sync legacy',
            'cargo_firma_id' => $cargoSync->id,
            'rol_revisor_id' => $roleSync->id,
            'activo' => true,
            'aplica_proyecto_inscripcion' => true,
        ]);

        return compact('flujo', 'proyecto', 'firmantes', 'empleadoSync');
    }

    private function proyecto(FlujoAprobacion $flujo): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto submit '.uniqid(),
            'codigo_proyecto' => 'SUBMIT-'.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $this->usuarioEmpleado('Empleado estado')[1]->id,
            'tipo_estado_id' => $this->tipoEstado('Borrador submit '.uniqid())->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return $proyecto->fresh();
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
            'numero_empleado' => 'SUBMIT-'.uniqid(),
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

class CreateProyectoVinculacionWorkflowStageSubmitComponent extends CreateProyectoVinculacion
{
    public bool $autoGuardadoLlamado = false;
    public bool $saveFirmasLlamado = false;
    public bool $guardarPorEtapaLlamado = false;
    public bool $fallarGuardadoPorEtapa = false;

    public function autoGuardarBorrador(): void
    {
        $this->autoGuardadoLlamado = true;
    }

    public function preparar(Proyecto $proyecto): void
    {
        $this->prepararEstadoFirmantesPorEtapa($proyecto);
    }

    public function seleccionar(Proyecto $proyecto, int $etapaId, int $empleadoId): void
    {
        $this->seleccionarFirmantePorEtapa($proyecto, $etapaId, $empleadoId);
    }

    protected function saveFirmas(Proyecto $record): void
    {
        $this->saveFirmasLlamado = true;
        parent::saveFirmas($record);
    }

    protected function guardarFirmasPorEtapaDesdeSeleccionDinamica(Proyecto $proyecto): Collection
    {
        $this->guardarPorEtapaLlamado = true;

        if ($this->fallarGuardadoPorEtapa) {
            throw new \RuntimeException('Falla controlada por etapa.');
        }

        return parent::guardarFirmasPorEtapaDesdeSeleccionDinamica($proyecto);
    }
}
