<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Docente\Proyectos\ProyectosDocenteList;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Dashboard\MisFormulariosService;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las cifras de la cabecera del panel docente abren Mi historial filtrado por el
 * mismo estado con el que se cuentan, así que la cifra y la lista deben coincidir.
 */
class CifrasNavegablesTest extends TestCase
{
    use DatabaseTransactions;

    private User $usuario;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        EstadoGeneral::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        $this->usuario = User::factory()->create();
        $this->empleado = Empleado::create([
            'nombre_completo' => 'Docente de cifras',
            'numero_empleado' => 'CIF-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $this->usuario->id,
        ]);
    }

    public function test_el_historial_filtrado_muestra_los_mismos_tramites_que_cuenta_el_panel(): void
    {
        $enRevision = $this->proyecto('Proyecto en revisión', 'En revision');
        $borradorAntiguo = $this->proyecto('Borrador sin estado', null);
        $anteriorAlSistema = $this->proyecto('Vinculación anterior al sistema', 'PendienteInformacion');
        $pps = $this->pps('Enviado');

        $resumen = (new MisFormulariosService)->resumen($this->empleado->id, $this->usuario->id);
        $this->assertSame(3, $resumen['total']);
        $this->assertSame(2, $resumen['en_revision']);

        // «En revisión» alcanza a todos los formularios, no solo a los proyectos.
        Livewire::actingAs($this->usuario)
            ->withQueryParams(['estado' => EstadoGeneral::EN_REVISION])
            ->test(ProyectosDocenteList::class)
            ->assertSet('filterEstado', EstadoGeneral::EN_REVISION)
            ->assertSee($enRevision->nombre_proyecto)
            ->assertSee($pps->nombre_estudiante)
            ->assertDontSee($borradorAntiguo->nombre_proyecto)
            ->assertDontSee($anteriorAlSistema->nombre_proyecto);

        // «Mis trámites»: el borrador antiguo (sin fila de estado) también aparece; la
        // vinculación anterior al sistema tiene su propia pantalla.
        Livewire::withQueryParams([])
            ->actingAs($this->usuario)
            ->test(ProyectosDocenteList::class)
            ->assertSet('filterEstado', '')
            ->assertSee($enRevision->nombre_proyecto)
            ->assertSee($borradorAntiguo->nombre_proyecto)
            ->assertSee($pps->nombre_estudiante)
            ->assertDontSee($anteriorAlSistema->nombre_proyecto);
    }

    public function test_la_cifra_enlaza_a_su_detalle(): void
    {
        $historial = Blade::render('<x-dashboard.cifra-cabecera :valor="5" etiqueta="En revisión" href="/proyectosDocente?estado=en_revision" />');
        $this->assertStringContainsString('<a', $historial);
        $this->assertStringContainsString('href="/proyectosDocente?estado=en_revision"', $historial);
        $this->assertStringContainsString('wire:navigate', $historial);

        // Las magnitudes abren un panel de la misma página en lugar de navegar.
        $panel = Blade::render('<x-dashboard.cifra-cabecera :valor="20" etiqueta="Horas registradas" href="#mi-aporte" />');
        $this->assertStringContainsString("\$dispatch('abrir-panel', 'mi-aporte')", $panel);
        $this->assertStringNotContainsString('wire:navigate', $panel);

        $sinDestino = Blade::render('<x-dashboard.cifra-cabecera :valor="1" etiqueta="Total" />');
        $this->assertStringNotContainsString('<a', $sinDestino);

        $expandible = Blade::render('<x-dashboard.panel-expandible id="mi-aporte" titulo="Mi aporte">Contenido</x-dashboard.panel-expandible>');
        $this->assertStringContainsString('id="mi-aporte"', $expandible);
        $this->assertStringContainsString('x-on:abrir-panel.window', $expandible);
    }

    private function proyecto(string $nombre, ?string $estado): Proyecto
    {
        $proyecto = Proyecto::create(['nombre_proyecto' => $nombre.' '.uniqid()]);
        DB::table('empleado_proyecto')->insert([
            'empleado_id' => $this->empleado->id, 'proyecto_id' => $proyecto->id, 'rol' => 'Coordinador',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($estado) {
            $this->ponerEstado($proyecto, $estado);
        }

        return $proyecto;
    }

    /** El estado del PPS sale de su fila actual en estado_proyecto («Enviado» = en revisión). */
    private function pps(string $estado): PpsServicioSocial
    {
        $registro = PpsServicioSocial::create([
            'codigo_registro' => 'PPS-'.uniqid(),
            'facultad_centro' => 'Facultad de prueba',
            'carrera' => 'Carrera de prueba',
            'numero_cuenta' => '2024'.random_int(10000000, 99999999),
            'nombre_estudiante' => 'Estudiante PPS '.uniqid(),
            'celular_estudiante' => '99999999',
            'correo_institucional' => 'pps@unah.edu.hn',
            'tipo_pps_ss' => 'Practica Profesional Supervisada',
            'fecha_inicio' => now()->toDateString(),
            'fecha_finalizacion' => now()->addMonths(6)->toDateString(),
            'tipo_instrumento' => 'Carta de Formalización',
            'territorio_ejecucion' => 'Nacional',
            'modalidad_ejecucion' => 'Presencial',
            'nombre_institucion' => 'Empresa de prueba',
            'nombre_jefe_directo' => 'Jefe',
            'cargo_jefe_directo' => 'Jefe de Recursos Humanos',
            'nombre_docente_supervisor' => 'Docente',
            'total_horas' => 120,
            'created_by' => $this->usuario->id,
        ]);
        $this->ponerEstado($registro, $estado);

        return $registro;
    }

    private function ponerEstado($modelo, string $estado): void
    {
        EstadoProyecto::withoutEvents(fn () => EstadoProyecto::create([
            'estadoable_type' => $modelo::class,
            'estadoable_id' => $modelo->id,
            'empleado_id' => $this->empleado->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => $estado])->id,
            'fecha' => now(),
            'es_actual' => true,
        ]));
    }
}
