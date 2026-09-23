<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Inicio\Dashboards\Dashboard;
use App\Models\ENF\EnfAccion;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Dashboard\PanelFormulariosService;
use App\Services\Dashboard\RegistroFormularios;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\Formularios\FormularioMotorComun;
use App\Support\Dashboard\Formularios\FormularioPanel;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El panel resume todos los formularios con el mismo vocabulario —estado
 * general, etapa actual y espera— sin conocer ninguno: los lee de
 * config('nexo.dashboard.formularios').
 *
 * Los escenarios usan el ámbito personal de un usuario nuevo para que las
 * cifras no dependan de lo que ya haya en la base.
 */
class PanelFormulariosTest extends TestCase
{
    use DatabaseTransactions;

    private User $usuario;

    private Empleado $empleado;

    private AmbitoPanel $personal;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        EstadoGeneral::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        $this->usuario = User::factory()->create();
        $this->empleado = Empleado::create([
            'nombre_completo' => 'Docente del panel',
            'numero_empleado' => 'PFO-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $this->usuario->id,
        ]);
        $this->personal = new AmbitoPanel(
            tipo: TipoAmbito::Personal,
            empleadoId: $this->empleado->id,
            userId: $this->usuario->id,
            etiqueta: 'Mis proyectos',
        );
    }

    public function test_todos_los_formularios_configurados_cumplen_el_contrato(): void
    {
        $formularios = app(RegistroFormularios::class)->todos();

        $this->assertCount(count(config('nexo.dashboard.formularios')), $formularios);
        $this->assertSame($formularios->count(), $formularios->map->codigo()->unique()->count());

        foreach ($formularios as $formulario) {
            $this->assertInstanceOf(FormularioPanel::class, $formulario);
            $this->assertSame(EstadoGeneral::CLAVES, array_keys($formulario->conteos($this->personal)));
        }
    }

    public function test_cada_formulario_traduce_sus_estados_a_los_cinco_generales(): void
    {
        $this->proyecto('VOLUNTARIADO', 'Enlace Vinculacion');
        $this->proyecto('VOLUNTARIADO', 'Registrado');
        $this->proyecto('VOLUNTARIADO', null);
        $this->proyecto(null, 'Finalizado');
        $this->pasantia('Rechazado');
        $this->enf('FORM-DVUS-016', 'APROBADO');

        $conteos = $this->conteosPorFormulario();

        $this->assertSame(1, $conteos['FORM-DVUS-015'][EstadoGeneral::EN_REVISION]);
        $this->assertSame(1, $conteos['FORM-DVUS-015'][EstadoGeneral::APROBADO]);
        $this->assertSame(1, $conteos['FORM-DVUS-015'][EstadoGeneral::BORRADOR]);
        $this->assertSame(1, $conteos['PROYECTO-SIN-TIPO'][EstadoGeneral::FINALIZADO]);
        $this->assertSame(1, $conteos['FORM-DVUS-013'][EstadoGeneral::SUBSANACION]);
        $this->assertSame(1, $conteos['FORM-DVUS-016'][EstadoGeneral::APROBADO]);

        $resumen = app(PanelFormulariosService::class)->resumen($this->personal);
        $this->assertSame(6, $resumen['total']);
    }

    public function test_la_matriz_agrupa_por_tipo_de_accion_y_omite_los_formularios_vacios(): void
    {
        $this->proyecto('VOLUNTARIADO', 'Registrado');
        $this->enf('FORM-DVUS-018', 'EN_REVISION');

        $matriz = collect(app(PanelFormulariosService::class)->matriz($this->personal))->keyBy('tipo_accion');

        $this->assertSame(['VOLUNTARIADO', 'EDUCACION_NO_FORMAL'], $matriz->keys()->sort()->reverse()->values()->all());
        $this->assertSame(['FORM-DVUS-015'], array_column($matriz['VOLUNTARIADO']['formularios'], 'codigo'));
        $this->assertSame(['FORM-DVUS-018'], array_column($matriz['EDUCACION_NO_FORMAL']['formularios'], 'codigo'));
        $this->assertSame(
            DB::table('vinculacion_tipos_accion')->where('codigo', 'VOLUNTARIADO')->value('nombre'),
            $matriz['VOLUNTARIADO']['etiqueta']
        );
    }

    public function test_un_formulario_nuevo_del_motor_comun_aparece_sin_tocar_codigo(): void
    {
        $codigo = $this->registrarFormularioDePrueba();
        $this->pasantia('Enviado');

        $formularios = collect(app(PanelFormulariosService::class)->matriz($this->personal))
            ->flatMap(fn (array $grupo) => $grupo['formularios'])
            ->keyBy('codigo');

        $this->assertArrayHasKey($codigo, $formularios->all());
        $this->assertSame(1, $formularios[$codigo]['conteos'][EstadoGeneral::EN_REVISION]);
    }

    public function test_atascos_y_detalle_cuentan_la_etapa_actual_desde_que_llego(): void
    {
        $codigo = $this->registrarFormularioDePrueba();
        $flujo = FlujoAprobacion::create([
            'codigo' => 'PRUEBA_'.uniqid(),
            'nombre' => 'Flujo de prueba',
            'proceso' => Pasantia::PROCESO_FLUJO,
            'codigo_formulario' => $codigo,
            'activo' => true,
        ]);
        [$primera, $segunda] = [$this->etapa($flujo, 1, 'Revisión de coordinación'), $this->etapa($flujo, 2, 'Visto bueno de dirección')];
        $pasantia = $this->pasantia('Enviado', $flujo);
        $this->firma($pasantia, $primera, creadaHace: 10);
        $this->firma($pasantia, $segunda, creadaHace: 10);

        $servicio = app(PanelFormulariosService::class);
        $atascos = collect($servicio->atascos($this->personal, 50))->where('codigo', $codigo)->values();

        $this->assertCount(1, $atascos);
        $this->assertSame('Revisión de coordinación', $atascos[0]['etapa']);
        $this->assertSame(1, $atascos[0]['tramites']);
        $this->assertSame(10, $atascos[0]['dias_maximo']);

        $detalle = $servicio->detalle($this->personal, $codigo);
        $etapas = collect($detalle['procesos'][0]['etapas'])->keyBy('etiqueta');

        $this->assertFalse($detalle['sin_flujo']);
        $this->assertSame(['Revisión de coordinación', 'Visto bueno de dirección'], $etapas->keys()->all());
        $this->assertSame(1, $etapas['Revisión de coordinación']['valor']);
        $this->assertSame(0, $etapas['Visto bueno de dirección']['valor']);
    }

    public function test_el_detalle_separa_los_expedientes_sin_adaptar_al_flujo(): void
    {
        // Recorrido anterior por cargos: firma sin flujo en el estado del cargo.
        $cargo = $this->cargo('Enlace heredado '.uniqid());
        $heredado = $this->proyecto('VOLUNTARIADO', null);
        $this->ponerEstado($heredado, $cargo->tipo_estado_id);
        $heredado->firma_proyecto()->create([
            'empleado_id' => $this->empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'heredada',
        ]);
        // Y uno que quedó «En revision» con todas sus firmas resueltas.
        $this->proyecto('VOLUNTARIADO', 'En revision');

        $heredados = collect(app(PanelFormulariosService::class)->detalle($this->personal, 'FORM-DVUS-015')['heredados'])
            ->keyBy('etiqueta');

        $this->assertSame(1, $heredados[$cargo->tipoCargoFirma->nombre]['valor'] ?? null);
        $this->assertSame(1, $heredados['En revision, sin firma pendiente']['valor'] ?? null);
    }

    public function test_un_formulario_que_no_sabe_acotarse_al_centro_lo_advierte(): void
    {
        $centro = new AmbitoPanel(tipo: TipoAmbito::Centro, centroFacultadId: 4, etiqueta: 'Centro');
        $registro = app(RegistroFormularios::class);

        $this->assertFalse($registro->porCodigo('FORM-DVUS-014')->admiteAmbito($centro));
        $this->assertTrue($registro->porCodigo('FORM-DVUS-001')->admiteAmbito($centro));
        $this->assertTrue($registro->porCodigo('FORM-DVUS-016')->admiteAmbito($centro));
    }

    public function test_el_panel_institucional_abre_el_recorrido_del_formulario_elegido(): void
    {
        Permission::findOrCreate('proyectos.historial', 'web');
        $rol = Role::findOrCreate('admin-formularios-'.uniqid(), 'web');
        $rol->syncPermissions(['proyectos.historial']);
        $this->usuario->assignRole($rol);
        $this->usuario->forceFill(['active_role_id' => $rol->id])->save();

        Livewire::actingAs($this->usuario->fresh())
            ->test(Dashboard::class)
            ->assertOk()
            ->call('verFormulario', 'FORM-DVUS-015')
            ->assertSet('formularioDetalle', 'FORM-DVUS-015')
            ->assertSee('Recorrido de FORM-DVUS-015')
            ->assertSee('Dónde se atascan los trámites');
    }

    // ── Escenarios ──────────────────────────────────────────────────────────

    /** @return array<string, array<string,int>> */
    private function conteosPorFormulario(): array
    {
        return collect(app(PanelFormulariosService::class)->matriz($this->personal))
            ->flatMap(fn (array $grupo) => $grupo['formularios'])
            ->mapWithKeys(fn (array $f) => [$f['codigo'] => $f['conteos']])
            ->all();
    }

    private function registrarFormularioDePrueba(): string
    {
        $codigo = 'FORM-PRUEBA-'.uniqid();
        config()->push('nexo.dashboard.formularios', [
            'clase' => FormularioMotorComun::class,
            'codigo' => $codigo,
            'nombre' => 'Formulario de prueba',
            'tipoAccion' => 'PASANTIAS',
            'modelo' => Pasantia::class,
        ]);

        return $codigo;
    }

    private function proyecto(?string $tipoAccion, ?string $estado): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto del panel '.uniqid(),
            'tipo_accion_id' => $tipoAccion ? DB::table('vinculacion_tipos_accion')->where('codigo', $tipoAccion)->value('id') : null,
        ]);
        DB::table('empleado_proyecto')->insert([
            'empleado_id' => $this->empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($estado) {
            $this->ponerEstado($proyecto, TipoEstado::firstOrCreate(['nombre' => $estado])->id);
        }

        return $proyecto;
    }

    private function pasantia(string $estado, ?FlujoAprobacion $flujo = null): Pasantia
    {
        $pasantia = Pasantia::create([
            'codigo_registro' => 'PAS-'.uniqid(),
            'proceso' => Pasantia::PROCESO_FLUJO,
            'estado' => 'en_revision',
            'nombre_estudiante' => 'Estudiante del panel',
            'flujo_aprobacion_id' => $flujo?->id,
            'created_by' => $this->usuario->id,
        ]);
        $this->ponerEstado($pasantia, TipoEstado::firstOrCreate(['nombre' => $estado])->id, Pasantia::class);

        return $pasantia;
    }

    private function enf(string $codigo, string $estadoFlujo): EnfAccion
    {
        return EnfAccion::create([
            'codigo_formulario' => $codigo,
            'nombre_accion' => 'Acción del panel '.uniqid(),
            'estado_flujo' => $estadoFlujo,
            'revision_ciclo' => 1,
            'creado_por_usuario_id' => $this->usuario->id,
        ]);
    }

    private function etapa(FlujoAprobacion $flujo, int $orden, string $nombre): FlujoAprobacionEtapa
    {
        return FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => $orden,
            'codigo' => 'PRUEBA_'.$orden,
            'nombre' => $nombre,
            'cargo_firma_id' => $this->cargo('Cargo '.$nombre.' '.uniqid())->id,
            'activo' => true,
        ]);
    }

    private function firma(Pasantia $pasantia, FlujoAprobacionEtapa $etapa, int $creadaHace = 0): void
    {
        $firma = $pasantia->firmasDeEtapa()->create([
            'empleado_id' => $this->empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'panel',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => 'Rol de prueba',
            'revision_ciclo' => 1,
        ]);
        $firma->forceFill(['created_at' => now()->subDays($creadaHace)])->saveQuietly();
    }

    private function ponerEstado($modelo, int $tipoEstadoId, string $tipo = Proyecto::class): void
    {
        EstadoProyecto::withoutEvents(fn () => EstadoProyecto::create([
            'estadoable_type' => $tipo,
            'estadoable_id' => $modelo->id,
            'empleado_id' => $this->empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]));
    }

    private function cargo(string $nombre): CargoFirma
    {
        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::create(['nombre' => $nombre])->id,
            'tipo_estado_id' => TipoEstado::create(['nombre' => 'Estado '.$nombre])->id,
        ]);
    }
}
