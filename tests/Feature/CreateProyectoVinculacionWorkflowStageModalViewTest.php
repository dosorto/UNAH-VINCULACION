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
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProyectoVinculacionWorkflowStageModalViewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vista_muestra_controles_dinamicos_y_conserva_controles_legacy(): void
    {
        $view = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('Preparar firmantes por etapa', $view);
        $this->assertStringContainsString('Validar firmantes por etapa', $view);
        $this->assertStringContainsString('Al activar el flujo por etapas, no se crearán las firmas legacy de jefe, decano/enlace.', $view);
        $this->assertStringContainsString('Activar firmantes por etapa para este envío', $view);
        $this->assertStringContainsString('Usar envío legacy', $view);
        $this->assertStringContainsString('wire:model="jefe_empleado_id"', $view);
        $this->assertStringContainsString('wire:model="decano_empleado_id"', $view);
        $this->assertStringContainsString('wire:model="enlace_empleado_id"', $view);
        $this->assertStringContainsString('wire:click="create"', $view);
    }

    public function test_preparar_firmantes_para_vista_falla_sin_record_y_no_llama_ensure_record(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalViewComponent;

        $component->prepararFirmantesPorEtapaParaVista();

        $this->assertFalse($component->ensureRecordLlamado);
        $this->assertTrue($component->mostrarFirmantesPorEtapa);
        $this->assertTrue($component->firmantesPorEtapaBloqueado);
        $this->assertSame('Debe guardar el borrador antes de preparar firmantes por etapa.', $component->mensajeBloqueoFirmantesPorEtapa);
    }

    public function test_preparar_firmantes_para_vista_muestra_etapas_candidatos_y_unidades(): void
    {
        [$authUser] = $this->usuarioEmpleado('Usuario vista');
        $this->actingAs($authUser);
        $context = $this->contexto();
        $role = $this->role('Rol vista');
        $centroConCandidato = $this->centro('Centro con candidato');
        $centroSinCandidato = $this->centro('Centro sin candidato');
        $proyecto = $this->proyecto($context['flujo'], centros: [$centroConCandidato, $centroSinCandidato]);
        $this->etapa($context, [
            'orden' => 1,
            'codigo' => 'ETAPA_VISIBLE',
            'nombre' => 'Etapa visible',
            'rol_revisor_id' => $role->id,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);
        $this->usuarioEmpleado('Candidato visible', $role, centro: $centroConCandidato);

        Livewire::actingAs($authUser)
            ->test(CreateProyectoVinculacion::class)
            ->set('recordId', $proyecto->id)
            ->set('proyectoId', $proyecto->id)
            ->set('currentStep', 9)
            ->call('prepararFirmantesPorEtapaParaVista')
            ->assertSet('mostrarFirmantesPorEtapa', true)
            ->assertSee('Etapa visible')
            ->assertSee('ETAPA_VISIBLE')
            ->assertSee(FlujoAprobacionEtapa::ALCANCE_CENTRO)
            ->assertSee(FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD)
            ->assertSee('Candidato visible')
            ->assertSee('Centro sin candidato')
            ->assertSee('Bloqueada')
            ->assertSee('La etapa requiere un revisor por unidad academica');
    }

    public function test_seleccionar_firmante_para_vista_actualiza_solo_etapa_y_rechaza_no_elegible(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalViewComponent;
        $component->jefe_empleado_id = 11;
        $component->decano_empleado_id = 22;
        $component->enlace_empleado_id = 33;
        $context = $this->contexto();
        $role = $this->role('Rol seleccion vista');
        $otroRole = $this->role('Rol no elegible vista');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['nombre' => 'Etapa seleccion', 'rol_revisor_id' => $role->id]);
        [, $empleadoElegible] = $this->usuarioEmpleado('Elegible vista', $role);
        [, $empleadoNoElegible] = $this->usuarioEmpleado('No elegible vista', $otroRole);
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;

        $component->prepararFirmantesPorEtapaParaVista();
        $component->seleccionarFirmantePorEtapaParaVista($etapa->id, $empleadoElegible->id);

        $this->assertSame($empleadoElegible->id, $component->firmantesPorEtapa[$etapa->id]['empleado_id']);

        $component->seleccionarFirmantePorEtapaParaVista($etapa->id, $empleadoNoElegible->id);

        $this->assertNull($component->firmantesPorEtapa[$etapa->id]['empleado_id']);
        $this->assertStringContainsString('no es elegible', $component->mensajesFirmantesPorEtapa[$etapa->id]);
        $this->assertSame(11, $component->jefe_empleado_id);
        $this->assertSame(22, $component->decano_empleado_id);
        $this->assertSame(33, $component->enlace_empleado_id);
    }

    public function test_validar_firmantes_para_vista_marca_listo_y_no_crea_firmas_o_estados(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalViewComponent;
        $context = $this->contexto();
        $role = $this->role('Rol validar vista');
        $proyecto = $this->proyecto($context['flujo']);
        $etapa = $this->etapa($context, ['nombre' => 'Etapa validar', 'rol_revisor_id' => $role->id]);
        [, $empleado] = $this->usuarioEmpleado('Validador vista', $role);
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;
        $firmas = FirmaProyecto::count();
        $estados = DB::table('estado_proyecto')->count();

        $component->prepararFirmantesPorEtapaParaVista();
        $component->validarFirmantesPorEtapaParaVista();

        $this->assertFalse($component->firmantesPorEtapaListos);
        $this->assertStringContainsString('Debe seleccionar un firmante', $component->mensajeBloqueoFirmantesPorEtapa);

        $component->seleccionarFirmantePorEtapaParaVista($etapa->id, $empleado->id);
        $component->validarFirmantesPorEtapaParaVista();

        $this->assertTrue($component->firmantesPorEtapaListos);
        $this->assertSame('Firmantes por etapa validados correctamente. Se activaran para envio en una fase posterior.', $component->mensajeFirmantesPorEtapaVista);
        $this->assertSame($firmas, FirmaProyecto::count());
        $this->assertSame($estados, DB::table('estado_proyecto')->count());
    }

    public function test_cerrar_seccion_no_toca_propiedades_legacy(): void
    {
        $component = new CreateProyectoVinculacionWorkflowStageModalViewComponent;
        $component->jefe_empleado_id = 101;
        $component->decano_empleado_id = 202;
        $component->enlace_empleado_id = 303;
        $component->mostrarFirmantesPorEtapa = true;
        $component->firmantesPorEtapa = [1 => ['empleado_id' => 404]];

        $component->cerrarFirmantesPorEtapaParaVista();

        $this->assertFalse($component->mostrarFirmantesPorEtapa);
        $this->assertSame([], $component->firmantesPorEtapa);
        $this->assertSame(101, $component->jefe_empleado_id);
        $this->assertSame(202, $component->decano_empleado_id);
        $this->assertSame(303, $component->enlace_empleado_id);
    }

    public function test_no_hay_integracion_productiva_con_firmas_por_etapa(): void
    {
        $component = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));
        $createBody = $this->methodBody($component, 'create');
        $saveFirmasBody = $this->methodBody($component, 'saveFirmas');
        $ensureRecordBody = $this->methodBody($component, 'ensureRecord');
        $view = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('$this->saveFirmas($record);', $createBody);
        $this->assertStringContainsString('$record->sincronizarFirmasDelFlujo();', $createBody);
        $this->assertStringNotContainsString('sincronizarFirmasDeEtapasDelFlujo', $createBody);
        $this->assertStringNotContainsString('asignacionesFirmantesPorEtapaNormalizadas', $createBody);
        $this->assertStringNotContainsString('validarFirmantesPorEtapaParaVista', $saveFirmasBody);
        $this->assertStringNotContainsString('prepararFirmantesPorEtapaParaVista', $ensureRecordBody);
        $this->assertStringContainsString('wire:click="create"', $view);
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

    private function contexto(): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_VISTA_'.uniqid(),
            'nombre' => 'Flujo vista',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado vista '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo vista '.uniqid()]);
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
            'nombre' => 'Etapa vista',
            'cargo_firma_id' => $context['cargo']->id,
            'activo' => true,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
        ], $attributes));
    }

    private function proyecto(?FlujoAprobacion $flujo = null, array $centros = []): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto vista '.uniqid(),
            'codigo_proyecto' => 'VST-'.uniqid(),
        ]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo?->id])->save();
        $proyecto->facultades_centros()->sync(collect($centros)->pluck('id')->all());

        return $proyecto->fresh();
    }

    private function role(string $name): Role
    {
        return Role::create([
            'name' => $name.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function usuarioEmpleado(string $name, ?Role $role = null, ?FacultadCentro $centro = null): array
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
            'numero_empleado' => 'EMP-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
            'centro_facultad_id' => $centro?->id,
        ]);

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
}

class CreateProyectoVinculacionWorkflowStageModalViewComponent extends CreateProyectoVinculacion
{
    public bool $ensureRecordLlamado = false;

    protected function ensureRecord(): Proyecto
    {
        $this->ensureRecordLlamado = true;

        return parent::ensureRecord();
    }
}
