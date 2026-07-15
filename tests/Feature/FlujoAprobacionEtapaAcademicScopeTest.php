<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FlujoAprobacionEtapaAcademicScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_esquema_agrega_campos_con_defaults_sin_modificar_columnas_legacy(): void
    {
        $this->assertTrue(Schema::hasColumn('flujos_aprobacion_etapas', 'alcance_academico'));
        $this->assertTrue(Schema::hasColumn('flujos_aprobacion_etapas', 'multiplicidad_revision'));

        $alcance = $this->columna('alcance_academico');
        $multiplicidad = $this->columna('multiplicidad_revision');
        $codigo = $this->columna('codigo');
        $activo = $this->columna('activo');

        $this->assertSame('NO', $alcance->Null);
        $this->assertSame('NO', $multiplicidad->Null);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $alcance->Default);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO, $multiplicidad->Default);
        $this->assertSame('varchar(80)', $codigo->Type);
        $this->assertSame('NO', $codigo->Null);
        $this->assertSame('1', (string) $activo->Default);
    }

    public function test_modelo_recibe_defaults_y_persiste_valores_permitidos(): void
    {
        $context = $this->crearContexto();
        $etapa = $this->crearEtapa($context['flujo'], $context['cargo']);

        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $etapa->refresh()->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO, $etapa->refresh()->multiplicidad_revision);

        foreach (array_keys(FlujoAprobacionEtapa::alcancesAcademicosDisponibles()) as $alcance) {
            $etapa->update(['alcance_academico' => $alcance]);
            $this->assertSame($alcance, $etapa->refresh()->alcance_academico);
        }

        foreach (array_keys(FlujoAprobacionEtapa::multiplicidadesRevisionDisponibles()) as $multiplicidad) {
            $etapa->update(['multiplicidad_revision' => $multiplicidad]);
            $this->assertSame($multiplicidad, $etapa->refresh()->multiplicidad_revision);
        }
    }

    public function test_constantes_listados_y_etiquetas_en_espanol(): void
    {
        $this->assertSame([
            FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            FlujoAprobacionEtapa::ALCANCE_GLOBAL,
            FlujoAprobacionEtapa::ALCANCE_CENTRO,
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO,
            FlujoAprobacionEtapa::ALCANCE_CARRERA,
            FlujoAprobacionEtapa::ALCANCE_PROYECTO,
        ], array_keys(FlujoAprobacionEtapa::alcancesAcademicosDisponibles()));

        $this->assertSame([
            FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
            FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ], array_keys(FlujoAprobacionEtapa::multiplicidadesRevisionDisponibles()));

        $this->assertSame('Sin filtro académico', FlujoAprobacionEtapa::alcancesAcademicosDisponibles()[FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO]);
        $this->assertSame('Global / Institucional', FlujoAprobacionEtapa::alcancesAcademicosDisponibles()[FlujoAprobacionEtapa::ALCANCE_GLOBAL]);
        $this->assertSame('Departamento académico', FlujoAprobacionEtapa::alcancesAcademicosDisponibles()[FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO]);
        $this->assertSame('Un único revisor', FlujoAprobacionEtapa::multiplicidadesRevisionDisponibles()[FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO]);
        $this->assertSame('Un revisor por cada unidad', FlujoAprobacionEtapa::multiplicidadesRevisionDisponibles()[FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD]);
    }

    public function test_metodos_de_consulta_validan_alcance_y_multiplicidad_sin_efectos(): void
    {
        foreach (array_keys(FlujoAprobacionEtapa::alcancesAcademicosDisponibles()) as $alcance) {
            $etapa = new FlujoAprobacionEtapa(['alcance_academico' => $alcance]);
            $this->assertTrue($etapa->tieneAlcanceAcademicoValido());
        }

        foreach ([null, '', 'OTRO'] as $alcance) {
            $etapa = new FlujoAprobacionEtapa(['alcance_academico' => $alcance]);
            $this->assertFalse($etapa->tieneAlcanceAcademicoValido());
        }

        foreach (array_keys(FlujoAprobacionEtapa::multiplicidadesRevisionDisponibles()) as $multiplicidad) {
            $etapa = new FlujoAprobacionEtapa(['multiplicidad_revision' => $multiplicidad]);
            $this->assertTrue($etapa->tieneMultiplicidadRevisionValida());
        }

        foreach ([null, '', 'OTRA'] as $multiplicidad) {
            $etapa = new FlujoAprobacionEtapa(['multiplicidad_revision' => $multiplicidad]);
            $this->assertFalse($etapa->tieneMultiplicidadRevisionValida());
        }
    }

    public function test_metodos_de_consulta_detectan_filtro_academico_y_revision_por_unidad(): void
    {
        foreach ([
            FlujoAprobacionEtapa::ALCANCE_CENTRO,
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO,
            FlujoAprobacionEtapa::ALCANCE_CARRERA,
        ] as $alcance) {
            $etapa = new FlujoAprobacionEtapa(['alcance_academico' => $alcance]);
            $this->assertTrue($etapa->requiereFiltroAcademico());
        }

        foreach ([
            FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            FlujoAprobacionEtapa::ALCANCE_GLOBAL,
            FlujoAprobacionEtapa::ALCANCE_PROYECTO,
            null,
            '',
            'OTRO',
        ] as $alcance) {
            $etapa = new FlujoAprobacionEtapa(['alcance_academico' => $alcance]);
            $this->assertFalse($etapa->requiereFiltroAcademico());
        }

        $this->assertTrue((new FlujoAprobacionEtapa([
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]))->requiereRevisionPorCadaUnidad());

        $this->assertFalse((new FlujoAprobacionEtapa([
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
        ]))->requiereRevisionPorCadaUnidad());
    }

    public function test_configuracion_de_flujos_actualiza_etapas_sin_borrar_metadatos_nuevos(): void
    {
        $context = $this->crearContexto();
        $etapa = $this->crearEtapa($context['flujo'], $context['cargo'], [
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_CENTRO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);

        (new ConfiguracionFlujosProyectosAcademicScopeComponent)->sincronizar($context['flujo'], [[
            'id' => $etapa->id,
            'orden' => 1,
            'codigo' => 'ETAPA_ACTUALIZADA',
            'aplica_inscripcion' => true,
            'aplica_informe_intermedio' => false,
            'aplica_cierre_proyecto' => false,
            'nombre' => 'Etapa actualizada',
            'tipo_etapa' => 'REVISION',
            'rol_revisor_id' => null,
            'usuario_responsable_id' => null,
            'cargo_firma_id' => $context['cargo']->id,
            'requiere_asignacion' => false,
            'emisor_define_destinatario' => false,
            'activo' => true,
        ]]);

        $etapa->refresh();
        $this->assertSame('ETAPA_ACTUALIZADA', $etapa->codigo);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_CENTRO, $etapa->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD, $etapa->multiplicidad_revision);
    }

    public function test_pps_enf_daft_y_creacion_actual_de_etapas_funcionan_sin_campos_nuevos(): void
    {
        $context = $this->crearContexto(proceso: 'PROYECTO');
        $pps = $this->crearEtapa($context['flujo'], $context['cargo'], [
            'codigo' => 'PPS_REVISION',
            'nombre' => 'Revision PPS',
            'estado_resultante' => 'en_revision',
            'permite_rechazo' => true,
        ]);
        $enf = $this->crearEtapa($context['flujo'], $context['cargo'], [
            'codigo' => 'ENF_REVISION',
            'nombre' => 'Revision ENF',
            'orden' => 2,
        ]);
        $daftContext = $this->crearContexto(proceso: 'PROGRAMA');
        $daft = $this->crearEtapa($daftContext['flujo'], $daftContext['cargo'], [
            'codigo' => 'DAFT_REVISION',
            'nombre' => 'Revision DAFT',
        ]);

        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $pps->refresh()->alcance_academico);
        $this->assertSame(FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO, $enf->refresh()->multiplicidad_revision);
        $this->assertSame(FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO, $daft->refresh()->alcance_academico);
        $this->assertCount(2, $context['flujo']->fresh('etapas')->etapas);
        $this->assertCount(1, $daftContext['flujo']->fresh('etapas')->etapas);
    }

    public function test_metadatos_no_cambian_creacion_autorizacion_avance_ni_reenvio_de_firmas(): void
    {
        $context = $this->crearContexto();
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto alcance '.uniqid(),
            'codigo_proyecto' => 'ALC-'.uniqid(),
        ]);
        $etapa = $this->crearEtapa($context['flujo'], $context['cargo'], [
            'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO,
            'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD,
        ]);
        $user = User::create([
            'name' => 'Usuario alcance',
            'email' => 'alcance-'.uniqid().'@unah.test',
        ]);
        $empleado = \App\Models\Personal\Empleado::create([
            'nombre_completo' => 'Empleado alcance',
            'numero_empleado' => 'ALC-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);

        $firma = $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [
            'estado_revision' => 'Pendiente',
        ]);

        $this->assertInstanceOf(FirmaProyecto::class, $firma);
        $this->assertSame($etapa->id, $firma->flujo_aprobacion_etapa_id);
        $this->assertSame('Pendiente', $firma->estado_revision);
        $this->assertSame($firma->id, $proyecto->firmaActualDeEtapasDelFlujo($context['flujo']->id)?->id);
        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($context['flujo']->id));
    }

    private function columna(string $nombre): object
    {
        return DB::selectOne('SHOW COLUMNS FROM flujos_aprobacion_etapas WHERE Field = ?', [$nombre]);
    }

    private function crearContexto(string $proceso = 'PROYECTO'): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_ALCANCE_'.uniqid(),
            'nombre' => 'Flujo alcance',
            'proceso' => $proceso,
            'activo' => true,
        ]);

        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado alcance '.uniqid()]);
        $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo alcance '.uniqid()]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $estado->id,
        ]);

        return compact('flujo', 'cargo');
    }

    private function crearEtapa(FlujoAprobacion $flujo, CargoFirma $cargo, array $attributes = []): FlujoAprobacionEtapa
    {
        return $flujo->etapas()->create(array_merge([
            'orden' => 1,
            'codigo' => 'ETAPA_'.uniqid(),
            'nombre' => 'Etapa alcance',
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
        ], $attributes));
    }
}

class ConfiguracionFlujosProyectosAcademicScopeComponent extends ConfiguracionFlujosProyectos
{
    public function sincronizar(FlujoAprobacion $flow, array $stages): void
    {
        $this->syncFlowStages($flow, $stages);
    }
}
