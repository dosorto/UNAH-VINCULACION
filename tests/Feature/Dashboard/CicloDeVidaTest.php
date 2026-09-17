<?php

namespace Tests\Feature\Dashboard;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Services\Dashboard\PanelEstadisticoService;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El ciclo de vida de un proyecto no acaba cuando se aprueba: sigue con el
 * informe intermedio —cuando su flujo lo contempla— y con el informe final que
 * lo cierra. El recorrido del panel solo modelaba la inscripción, así que un
 * proyecto en fase de cierre se seguía contando como "en curso".
 */
class CicloDeVidaTest extends TestCase
{
    use DatabaseTransactions;

    private PanelEstadisticoService $servicio;

    private AmbitoPanel $ambito;

    private int $empleadoId;

    private int $cargoFirmaId;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        $this->servicio = app(PanelEstadisticoService::class);
        $this->ambito = new AmbitoPanel(tipo: TipoAmbito::Global, etiqueta: 'Prueba');

        // estado_proyecto y firma_proyecto exigen empleado y cargo; la base de
        // pruebas puede estar recién migrada y sin catálogos.
        $this->empleadoId = $this->empleadoDePrueba();
        $this->cargoFirmaId = $this->cargoFirmaDePrueba();
    }

    private function empleadoDePrueba(): int
    {
        $existente = DB::table('empleado')->value('id');

        if ($existente) {
            return (int) $existente;
        }

        $usuarioId = DB::table('users')->value('id')
            ?: DB::table('users')->insertGetId([
                'name' => 'Usuario de prueba',
                'email' => 'ciclo-'.uniqid().'@unah.hn',
                'password' => bcrypt('secret'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return (int) DB::table('empleado')->insertGetId([
            'user_id' => $usuarioId,
            'nombre_completo' => 'Empleado de prueba',
            'numero_empleado' => 'CICLO-'.random_int(10000, 99999),
            'tipo_empleado' => 'docente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function cargoFirmaDePrueba(): int
    {
        $existente = DB::table('cargo_firma')->value('id');

        if ($existente) {
            return (int) $existente;
        }

        $tipoCargoId = DB::table('tipo_cargo_firma')->value('id')
            ?: DB::table('tipo_cargo_firma')->insertGetId([
                'nombre' => 'Cargo de prueba',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return (int) DB::table('cargo_firma')->insertGetId([
            'descripcion' => 'Cargo de prueba',
            'tipo_cargo_firma_id' => $tipoCargoId,
            'tipo_estado_id' => EstadosProyecto::ids(EstadosProyecto::EN_CURSO)[0]
                ?? TipoEstado::create(['nombre' => EstadosProyecto::EN_CURSO])->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_las_fases_cubren_siempre_el_total_de_proyectos(): void
    {
        $ciclo = $this->servicio->cicloDeVida($this->ambito);
        $total = $this->servicio->resumenEstados($this->ambito)['total'];

        $suma = array_sum(array_column($ciclo['fases'], 'valor')) + $ciclo['sin_iniciar'];

        $this->assertSame(
            $total,
            $suma,
            'Las fases más los borradores deben sumar el total: un resto sin explicar deja cifras que no cuadran.'
        );
    }

    public function test_el_recorrido_recoge_las_cinco_fases_del_proyecto(): void
    {
        $claves = array_column($this->servicio->cicloDeVida($this->ambito)['fases'], 'clave');

        $this->assertSame(
            ['revision', 'ejecucion', 'intermedio', 'final', 'cerrado'],
            $claves,
            'El orden importa: es el camino que recorre el expediente.'
        );
    }

    public function test_los_borradores_quedan_fuera_del_recorrido(): void
    {
        // No han entrado al flujo, así que como etapa no dicen nada; se cuentan
        // aparte para no inflar la primera fase.
        $ciclo = $this->servicio->cicloDeVida($this->ambito);

        $this->assertArrayHasKey('sin_iniciar', $ciclo);
        $this->assertNotContains('borrador', array_column($ciclo['fases'], 'clave'));
        $this->assertNotContains('Sin enviar', array_column($ciclo['fases'], 'etiqueta'));
    }

    public function test_un_proyecto_con_informe_final_en_flujo_deja_de_contarse_como_en_curso(): void
    {
        $proyecto = $this->proyectoEnEstado(EstadosProyecto::EN_CURSO);

        $antes = $this->servicio->cicloDeVida($this->ambito);
        $this->reiniciarServicio();

        $this->documentoConFirma($proyecto, 'Informe Final');

        $despues = $this->servicio->cicloDeVida($this->ambito);

        $this->assertSame(
            $this->fase($antes, 'ejecucion') - 1,
            $this->fase($despues, 'ejecucion'),
            'El proyecto debe salir de "en ejecución".'
        );
        $this->assertSame(
            $this->fase($antes, 'final') + 1,
            $this->fase($despues, 'final'),
            'Y aparecer en la fase de informe final.'
        );
    }

    public function test_el_informe_intermedio_situa_al_proyecto_en_su_propia_fase(): void
    {
        $proyecto = $this->proyectoEnEstado(EstadosProyecto::EN_CURSO);

        $antes = $this->servicio->cicloDeVida($this->ambito);
        $this->reiniciarServicio();

        $this->documentoConFirma($proyecto, 'Informe Intermedio');

        $despues = $this->servicio->cicloDeVida($this->ambito);

        $this->assertSame($this->fase($antes, 'intermedio') + 1, $this->fase($despues, 'intermedio'));
    }

    public function test_el_informe_final_manda_sobre_el_intermedio(): void
    {
        // Un proyecto que ya envió el cierre está en cierre, aunque conserve el
        // intermedio de antes.
        $proyecto = $this->proyectoEnEstado(EstadosProyecto::EN_CURSO);
        $this->documentoConFirma($proyecto, 'Informe Intermedio');
        $this->documentoConFirma($proyecto, 'Informe Final');

        $ciclo = $this->servicio->cicloDeVida($this->ambito);

        $this->assertGreaterThan(0, $this->fase($ciclo, 'final'));
    }

    public function test_un_documento_sin_firmas_no_cambia_la_fase(): void
    {
        // Redactar el informe no es haberlo enviado: solo cuenta cuando entra al
        // flujo de firmas.
        $proyecto = $this->proyectoEnEstado(EstadosProyecto::EN_CURSO);

        $antes = $this->servicio->cicloDeVida($this->ambito);
        $this->reiniciarServicio();

        DB::table('proyecto_documento')->insert([
            'proyecto_id' => $proyecto->id,
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'pruebas/informe.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $despues = $this->servicio->cicloDeVida($this->ambito);

        $this->assertSame($this->fase($antes, 'final'), $this->fase($despues, 'final'));
        $this->assertSame($this->fase($antes, 'ejecucion'), $this->fase($despues, 'ejecucion'));
    }

    /** @param array{fases:list<array{clave:string,valor:int}>} $ciclo */
    private function fase(array $ciclo, string $clave): int
    {
        foreach ($ciclo['fases'] as $fase) {
            if ($fase['clave'] === $clave) {
                return $fase['valor'];
            }
        }

        return 0;
    }

    private function reiniciarServicio(): void
    {
        // El servicio memoiza por petición; en la prueba hay que pedir uno nuevo
        // para volver a consultar.
        $this->servicio = app()->makeWith(PanelEstadisticoService::class, []);
        $this->servicio = new PanelEstadisticoService(app(\App\Services\Dashboard\AmbitoPanelResolver::class));
    }

    private function proyectoEnEstado(string $estado): Proyecto
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto de prueba '.uniqid(),
        ]);

        $tipoEstadoId = EstadosProyecto::ids($estado)[0]
            ?? TipoEstado::create(['nombre' => $estado])->id;

        DB::table('estado_proyecto')->insert([
            'empleado_id' => $this->empleadoId,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now()->toDateString(),
            'es_actual' => true,
            'estadoable_type' => Proyecto::class,
            'estadoable_id' => $proyecto->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->reiniciarServicio();

        return $proyecto;
    }

    private function documentoConFirma(Proyecto $proyecto, string $tipo): void
    {
        $documentoId = DB::table('proyecto_documento')->insertGetId([
            'proyecto_id' => $proyecto->id,
            'tipo_documento' => $tipo,
            'documento_url' => 'pruebas/informe.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('firma_proyecto')->insert([
            'empleado_id' => $this->empleadoId,
            'cargo_firma_id' => $this->cargoFirmaId,
            'estado_revision' => 'Pendiente',
            'firmable_type' => \App\Models\Proyecto\DocumentoProyecto::class,
            'firmable_id' => $documentoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->reiniciarServicio();
    }
}
