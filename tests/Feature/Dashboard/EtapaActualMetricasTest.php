<?php

namespace Tests\Feature\Dashboard;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Dashboard\AmbitoPanelResolver;
use App\Services\Dashboard\PanelEstadisticoService;
use App\Services\Dashboard\PanelFormulariosService;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use App\Support\Proyecto\EstadoGeneralProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Las métricas de salud del flujo cuentan la etapa que cada expediente espera
 * AHORA, no todas sus firmas pendientes.
 *
 * Al enviar se crean las firmas de todo el recorrido, y desde que un proyecto
 * con flujo queda "En revision" toda la inscripción, comparar su estado con el
 * del cargo ya no sirve para saber en qué etapa está.
 */
class EtapaActualMetricasTest extends TestCase
{
    use DatabaseTransactions;

    private AmbitoPanel $ambito;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);
        $this->ambito = new AmbitoPanel(TipoAmbito::Global, etiqueta: 'Toda la UNAH');
    }

    public function test_en_cola_cuenta_la_etapa_actual_de_un_proyecto_en_revision(): void
    {
        [$proyecto, $etapas] = $this->proyectoEnRevision(['Zeta primera '.uniqid(), 'Alfa segunda '.uniqid()]);
        $primera = $this->firma($proyecto, $etapas[0]);
        $this->firma($proyecto, $etapas[1]);

        $esperando = $this->panel()->detenidosPorEtapa($this->ambito);

        $this->assertSame(1, $esperando[mb_strtolower($etapas[0]->nombre)]['proyectos'] ?? null);
        $this->assertArrayNotHasKey(mb_strtolower($etapas[1]->nombre), $esperando);

        $primera->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);
        $esperando = $this->panel()->detenidosPorEtapa($this->ambito);

        $this->assertArrayNotHasKey(mb_strtolower($etapas[0]->nombre), $esperando);
        $this->assertSame(1, $esperando[mb_strtolower($etapas[1]->nombre)]['proyectos'] ?? null);
    }

    public function test_un_rechazo_detiene_la_cola_y_el_reenvio_la_reanuda_en_el_ciclo_nuevo(): void
    {
        [$proyecto, $etapas] = $this->proyectoEnRevision(['Rechazo primera '.uniqid(), 'Rechazo segunda '.uniqid()]);
        $this->firma($proyecto, $etapas[0], ['estado_revision' => 'Rechazado']);
        $this->firma($proyecto, $etapas[1]);
        $this->cambiarEstado($proyecto, 'Subsanacion');

        $esperando = $this->panel()->detenidosPorEtapa($this->ambito);
        $this->assertArrayNotHasKey(mb_strtolower($etapas[1]->nombre), $esperando);

        $this->cambiarEstado($proyecto, 'En revision');
        $this->firma($proyecto, $etapas[0], ['revision_ciclo' => 2]);
        $esperando = $this->panel()->detenidosPorEtapa($this->ambito);

        $this->assertSame(1, $esperando[mb_strtolower($etapas[0]->nombre)]['proyectos'] ?? null);
        $this->assertArrayNotHasKey(mb_strtolower($etapas[1]->nombre), $esperando);
    }

    public function test_detenidos_muestran_la_etapa_actual_y_la_espera_desde_que_llego(): void
    {
        [$proyecto, $etapas] = $this->proyectoEnRevision(['Zeta aprobada '.uniqid(), 'Alfa en espera '.uniqid(), 'Beta futura '.uniqid()]);
        $this->firma($proyecto, $etapas[0], ['estado_revision' => 'Aprobado', 'fecha_firma' => now()->subDays(20)], creadaHace: 40);
        $this->firma($proyecto, $etapas[1], creadaHace: 40);
        $this->firma($proyecto, $etapas[2], creadaHace: 40);

        $fila = collect($this->panel()->cuellosDeBotella($this->ambito, 1000))->firstWhere('proyecto_id', $proyecto->id);

        $this->assertNotNull($fila);
        $this->assertSame('Proyecto', $fila['tipo']);
        // Antes: MIN() alfabético entre todas las pendientes, y días desde el envío.
        $this->assertSame($etapas[1]->nombre, $fila['etapa']);
        $this->assertSame(20, $fila['dias']);
    }

    public function test_detenidos_incluyen_los_informes_en_revision(): void
    {
        [$proyecto, $etapas] = $this->proyectoEnRevision(['Cierre '.uniqid()]);
        $this->cambiarEstado($proyecto, 'Registrado');
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $this->firma($documento, $etapas[0], creadaHace: 16);

        $fila = collect($this->panel()->cuellosDeBotella($this->ambito, 1000, 14))->firstWhere('proyecto_id', $proyecto->id);

        $this->assertNotNull($fila);
        $this->assertSame('Informe Final', $fila['tipo']);
        $this->assertSame($etapas[0]->nombre, $fila['etapa']);
        $this->assertSame(16, $fila['dias']);
    }

    public function test_el_tiempo_de_una_etapa_se_mide_desde_que_le_llego(): void
    {
        [$proyecto, $etapas] = $this->proyectoEnRevision(['Tiempo primera '.uniqid(), 'Tiempo segunda '.uniqid()]);
        $this->firma($proyecto, $etapas[0], ['estado_revision' => 'Aprobado', 'fecha_firma' => now()->subDays(6)], creadaHace: 10);
        $this->firma($proyecto, $etapas[1], ['estado_revision' => 'Aprobado', 'fecha_firma' => now()->subDay()], creadaHace: 10);

        $tiempos = collect($this->panel()->tiemposPorEtapa($this->ambito, 1000))->keyBy('etiqueta');

        $this->assertSame(4.0, $tiempos[$etapas[0]->nombre]['valor']);
        // Antes: 9 días, contando la espera de la primera etapa.
        $this->assertSame(5.0, $tiempos[$etapas[1]->nombre]['valor']);
    }

    public function test_un_proyecto_finalizado_cuenta_como_cerrado_aunque_tenga_informe_final(): void
    {
        $resumenAntes = app(PanelFormulariosService::class)->resumen($this->ambito)['conteos'];
        $cicloAntes = $this->fases($this->panel()->cicloDeVida($this->ambito));

        [$proyecto, $etapas] = $this->proyectoEnRevision(['Cierre final '.uniqid()]);
        $this->cambiarEstado($proyecto, 'Finalizado');
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $this->firma($documento, $etapas[0], ['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);

        $resumen = app(PanelFormulariosService::class)->resumen($this->ambito)['conteos'];
        $ciclo = $this->fases($this->panel()->cicloDeVida($this->ambito));

        $this->assertSame($resumenAntes[EstadoGeneral::FINALIZADO] + 1, $resumen[EstadoGeneral::FINALIZADO]);
        $this->assertSame($resumenAntes[EstadoGeneral::APROBADO], $resumen[EstadoGeneral::APROBADO]);
        $this->assertSame($cicloAntes['cerrado'] + 1, $ciclo['cerrado']);
        $this->assertSame($cicloAntes['final'], $ciclo['final']);
    }

    // ── Escenarios ──────────────────────────────────────────────────────────

    /** @return array{0:Proyecto,1:list<FlujoAprobacionEtapa>} */
    private function proyectoEnRevision(array $nombresEtapas): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto métricas '.uniqid(),
            'codigo_proyecto' => 'MET-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_MET_'.uniqid(),
            'nombre' => 'Flujo métricas',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->saveQuietly();
        $this->cambiarEstado($proyecto, 'En revision');

        $etapas = [];
        foreach (array_values($nombresEtapas) as $i => $nombre) {
            $etapas[] = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $i + 1,
                'codigo' => 'MET_'.($i + 1).'_'.uniqid(),
                'nombre' => $nombre,
                'cargo_firma_id' => $this->cargoFirma()->id,
                'activo' => true,
            ]);
        }

        return [$proyecto, $etapas];
    }

    private function firma(Proyecto|DocumentoProyecto $firmable, FlujoAprobacionEtapa $etapa, array $atributos = [], int $creadaHace = 0): FirmaProyecto
    {
        $relacion = $firmable instanceof Proyecto ? $firmable->firma_proyecto() : $firmable->firma_documento();

        $firma = $relacion->create(array_merge([
            'empleado_id' => $this->empleado()->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-metricas',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => 'Rol métricas',
            'revision_ciclo' => 1,
        ], $atributos));

        if ($creadaHace > 0) {
            $firma->forceFill(['created_at' => now()->subDays($creadaHace)])->saveQuietly();
        }

        return $firma;
    }

    private function cambiarEstado(Proyecto $proyecto, string $nombre): void
    {
        $proyecto->estado_proyecto()->update(['es_actual' => false]);
        EstadoProyecto::create([
            'estadoable_type' => Proyecto::class,
            'estadoable_id' => $proyecto->id,
            'empleado_id' => $this->empleado()->id,
            'tipo_estado_id' => EstadoGeneralProyecto::id($nombre),
            'fecha' => now(),
            'es_actual' => true,
        ]);
    }

    private function panel(): PanelEstadisticoService
    {
        return new PanelEstadisticoService(app(AmbitoPanelResolver::class));
    }

    /** @return array<string,int> */
    private function fases(array $ciclo): array
    {
        return collect($ciclo['fases'])->mapWithKeys(fn (array $f): array => [$f['clave'] => $f['valor']])->all();
    }

    private function empleado(): Empleado
    {
        $user = User::create(['name' => 'Empleado métricas', 'email' => 'metricas-'.uniqid().'@unah.test']);

        return Empleado::create([
            'nombre_completo' => 'Empleado métricas',
            'numero_empleado' => 'MET-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function cargoFirma(): CargoFirma
    {
        $nombre = 'Cargo métricas '.uniqid();

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::create(['nombre' => $nombre])->id,
            'tipo_estado_id' => TipoEstado::create(['nombre' => 'Estado '.$nombre])->id,
        ]);
    }
}
