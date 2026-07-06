<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\SGCU\TipoPrograma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConfiguracionFlujosAcademicScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pantalla_muestra_selectores_y_opciones_de_alcance_academico(): void
    {
        $this->crearCatalogosBase();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->assertSee('Alcance academico')
            ->assertSee('Sin filtro académico')
            ->assertSee('Global / Institucional')
            ->assertSee('Departamento académico')
            ->assertSee('Define de qué unidad académica saldrá el revisor de esta etapa.')
            ->assertSee('Multiplicidad')
            ->assertSee('Define si se seleccionará un único revisor o uno por cada unidad académica.')
            ->assertSee('Un revisor por cada unidad')
            ->call('showProgramFlows')
            ->assertSee('Alcance academico')
            ->assertSee('Carrera')
            ->assertSee('Un único revisor');
    }

    public function test_guardar_flujo_de_proyecto_persiste_alcance_y_multiplicidad(): void
    {
        $context = $this->crearCatalogosBase();
        $role = $this->crearRol();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->set('workflow.codigo', 'FLUJO_CONFIG_PROYECTO_'.uniqid())
            ->set('workflow.nombre', 'Flujo config proyecto')
            ->set('stages.0.codigo', 'REVISION_CENTRO')
            ->set('stages.0.nombre', 'Revision por centro')
            ->set('stages.0.tipo_etapa', 'REVISION')
            ->set('stages.0.rol_revisor_id', (string) $role->id)
            ->set('stages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('stages.0.alcance_academico', FlujoAprobacionEtapa::ALCANCE_CENTRO)
            ->set('stages.0.multiplicidad_revision', FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD)
            ->call('save')
            ->assertHasNoErrors();

        $etapa = FlujoAprobacionEtapa::query()->where('codigo', 'REVISION_CENTRO')->firstOrFail();

        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_CENTRO, $etapa->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD, $etapa->multiplicidad_revision);
    }

    public function test_guardar_flujo_sgcu_persiste_alcance_y_multiplicidad(): void
    {
        $context = $this->crearCatalogosBase();
        $role = $this->crearRol();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->call('showProgramFlows')
            ->set('programWorkflow.codigo', 'FLUJO_CONFIG_SGCU_'.uniqid())
            ->set('programWorkflow.nombre', 'Flujo config SGCU')
            ->set('programStages.0.codigo', 'REVISION_CARRERA')
            ->set('programStages.0.nombre', 'Revision por carrera')
            ->set('programStages.0.rol_revisor_id', (string) $role->id)
            ->set('programStages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('programStages.0.alcance_academico', FlujoAprobacionEtapa::ALCANCE_CARRERA)
            ->set('programStages.0.multiplicidad_revision', FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD)
            ->call('save')
            ->assertHasNoErrors();

        $etapa = FlujoAprobacionEtapa::query()->where('codigo', 'REVISION_CARRERA')->firstOrFail();

        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_CARRERA, $etapa->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD, $etapa->multiplicidad_revision);
    }

    public function test_valida_multiplicidad_por_unidad_solo_en_alcances_academicos_filtrables(): void
    {
        $context = $this->crearCatalogosBase();
        $role = $this->crearRol();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->set('workflow.codigo', 'FLUJO_CONFIG_INVALIDO_'.uniqid())
            ->set('workflow.nombre', 'Flujo config invalido')
            ->set('stages.0.codigo', 'REVISION_GLOBAL')
            ->set('stages.0.nombre', 'Revision global')
            ->set('stages.0.rol_revisor_id', (string) $role->id)
            ->set('stages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('stages.0.alcance_academico', FlujoAprobacionEtapa::ALCANCE_GLOBAL)
            ->set('stages.0.multiplicidad_revision', FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD)
            ->call('save')
            ->assertHasErrors(['stages.0.multiplicidad_revision']);
    }

    public function test_rechaza_valores_fuera_de_catalogo_para_alcance_y_multiplicidad(): void
    {
        $context = $this->crearCatalogosBase();
        $role = $this->crearRol();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->set('workflow.codigo', 'FLUJO_CONFIG_CATALOGO_'.uniqid())
            ->set('workflow.nombre', 'Flujo config catalogo')
            ->set('stages.0.codigo', 'REVISION_CATALOGO')
            ->set('stages.0.nombre', 'Revision catalogo')
            ->set('stages.0.rol_revisor_id', (string) $role->id)
            ->set('stages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('stages.0.alcance_academico', 'OTRO_ALCANCE')
            ->set('stages.0.multiplicidad_revision', 'OTRA_MULTIPLICIDAD')
            ->call('save')
            ->assertHasErrors([
                'stages.0.alcance_academico',
                'stages.0.multiplicidad_revision',
            ]);
    }

    public function test_valida_responsable_en_etapas_activas_con_alcance_academico(): void
    {
        $context = $this->crearCatalogosBase();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->set('workflow.codigo', 'FLUJO_CONFIG_SIN_RESPONSABLE_'.uniqid())
            ->set('workflow.nombre', 'Flujo config sin responsable')
            ->set('stages.0.codigo', 'REVISION_DEPARTAMENTO')
            ->set('stages.0.nombre', 'Revision por departamento')
            ->set('stages.0.rol_revisor_id', '')
            ->set('stages.0.usuario_responsable_id', '')
            ->set('stages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('stages.0.alcance_academico', FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO)
            ->set('stages.0.multiplicidad_revision', FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO)
            ->call('save')
            ->assertHasErrors(['stages.0.rol_revisor_id']);
    }

    public function test_etapa_inactiva_con_alcance_academico_no_exige_responsable(): void
    {
        $context = $this->crearCatalogosBase();
        $this->actingAs(User::factory()->create());

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->set('workflow.codigo', 'FLUJO_CONFIG_INACTIVA_'.uniqid())
            ->set('workflow.nombre', 'Flujo config inactiva')
            ->set('stages.0.codigo', 'REVISION_INACTIVA')
            ->set('stages.0.nombre', 'Revision inactiva')
            ->set('stages.0.rol_revisor_id', '')
            ->set('stages.0.usuario_responsable_id', '')
            ->set('stages.0.cargo_firma_id', (string) $context['cargo']->id)
            ->set('stages.0.activo', false)
            ->set('stages.0.alcance_academico', FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO)
            ->set('stages.0.multiplicidad_revision', FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO)
            ->call('save')
            ->assertHasNoErrors();

        $etapa = FlujoAprobacionEtapa::query()->where('codigo', 'REVISION_INACTIVA')->firstOrFail();

        $this->assertFalse((bool) $etapa->activo);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO, $etapa->alcance_academico);
    }

    public function test_pps_sugeridas_y_payloads_legacy_usan_defaults_sin_borrar_valores_existentes(): void
    {
        $context = $this->crearCatalogosBase();
        $component = new ConfiguracionFlujosAcademicScopeComponentFake();
        $etapaExistente = $context['flujo']->etapas()->create([
            'orden' => 1,
            'codigo' => 'ETAPA_EXISTENTE',
            'nombre' => 'Etapa existente',
            'cargo_firma_id' => $context['cargo']->id,
            'activo' => true,
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);

        $component->sincronizar($context['flujo'], [
            [
                'id' => $etapaExistente->id,
                'orden' => 1,
                'codigo' => 'ETAPA_EXISTENTE_ACTUALIZADA',
                'aplica_inscripcion' => true,
                'aplica_informe_intermedio' => false,
                'aplica_cierre_proyecto' => false,
                'nombre' => 'Etapa existente actualizada',
                'tipo_etapa' => 'REVISION',
                'rol_revisor_id' => null,
                'usuario_responsable_id' => null,
                'cargo_firma_id' => $context['cargo']->id,
                'requiere_asignacion' => false,
                'emisor_define_destinatario' => false,
                'activo' => true,
            ],
            [
                'id' => null,
                'orden' => 2,
                'codigo' => 'ETAPA_NUEVA_LEGACY',
                'aplica_inscripcion' => true,
                'aplica_informe_intermedio' => false,
                'aplica_cierre_proyecto' => false,
                'nombre' => 'Etapa nueva legacy',
                'tipo_etapa' => 'REVISION',
                'rol_revisor_id' => null,
                'usuario_responsable_id' => null,
                'cargo_firma_id' => $context['cargo']->id,
                'requiere_asignacion' => false,
                'emisor_define_destinatario' => false,
                'activo' => true,
            ],
        ]);

        $etapaExistente->refresh();
        $etapaNueva = FlujoAprobacionEtapa::query()->where('codigo', 'ETAPA_NUEVA_LEGACY')->firstOrFail();
        $ppsSugeridas = $component->ppsSugeridas();

        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_CENTRO, $etapaExistente->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD, $etapaExistente->multiplicidad_revision);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $etapaNueva->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO, $etapaNueva->multiplicidad_revision);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $ppsSugeridas[0]['alcance_academico']);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO, $ppsSugeridas[1]['multiplicidad_revision']);
    }

    private function crearCatalogosBase(): array
    {
        DB::table('vinculacion_tipos_accion')->updateOrInsert(
            ['codigo' => 'DESARROLLO_LOCAL_REGIONAL'],
            [
                'nombre' => 'Desarrollo local y regional',
                'descripcion' => 'Accion de prueba',
                'activo' => true,
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        TipoPrograma::create([
            'nombre' => 'Tipo config '.uniqid(),
            'activo' => true,
        ]);

        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado config alcance']);
        $tipoCargo = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor Vinculacion']);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $estado->id,
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_CONFIG_LEGACY_'.uniqid(),
            'nombre' => 'Flujo config legacy',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);

        return compact('cargo', 'flujo');
    }

    private function crearRol(): Role
    {
        return Role::create([
            'name' => 'Revisor config '.uniqid(),
            'guard_name' => 'web',
        ]);
    }
}

class ConfiguracionFlujosAcademicScopeComponentFake extends ConfiguracionFlujosProyectos
{
    public function sincronizar(FlujoAprobacion $flow, array $stages): void
    {
        $this->syncFlowStages($flow, $stages);
    }

    public function ppsSugeridas(): array
    {
        return $this->defaultPpsStages();
    }
}
