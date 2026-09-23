<?php

namespace Tests\Feature\Dashboard;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Dashboard\ActividadRecienteService;
use App\Services\Dashboard\MisFormulariosService;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Defectos menores del panel encontrados al revisarlo con los datos
 * importados de producción.
 */
class PanelDetallesTest extends TestCase
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
            'nombre_completo' => 'Docente de detalles',
            'numero_empleado' => 'DET-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $this->usuario->id,
        ]);
    }

    public function test_la_actividad_nombra_el_informe_y_enlaza_su_proyecto(): void
    {
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Huertos escolares '.uniqid()]);
        DB::table('empleado_proyecto')->insert([
            'empleado_id' => $this->empleado->id, 'proyecto_id' => $proyecto->id, 'rol' => 'Coordinador',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        EstadoProyecto::withoutEvents(fn () => $documento->estado_documento()->create([
            'empleado_id' => $this->empleado->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Enlace Vinculacion'])->id,
            'fecha' => now(),
            'es_actual' => true,
        ]));

        $item = app(ActividadRecienteService::class)
            ->para(new AmbitoPanel(TipoAmbito::Personal, empleadoId: $this->empleado->id, userId: $this->usuario->id))
            ->sole();

        // proyecto_documento no tiene nombre: antes salía «Documento» y sin enlace.
        $this->assertSame('Informe Final · '.$proyecto->nombre_proyecto, $item->nombre_elemento);
        $this->assertSame(route('historialproyecto', $proyecto->id), $item->href);
    }

    public function test_mis_formularios_incluye_pasantias_y_cuenta_el_pps_autorizado(): void
    {
        $pasantia = Pasantia::create([
            'codigo_registro' => 'PAS-'.uniqid(),
            'proceso' => Pasantia::PROCESO_FLUJO,
            'estado' => 'borrador',
            'nombre_estudiante' => 'Estudiante en pasantía',
            'created_by' => $this->usuario->id,
        ]);
        $pps = $this->pps();
        $this->ponerEstado($pps, 'Aprobado', PpsServicioSocial::class);

        $servicio = app(MisFormulariosService::class);
        $filas = $servicio->para($this->empleado->id, $this->usuario->id)->keyBy('kind');
        $resumen = $servicio->resumen($this->empleado->id, $this->usuario->id);

        $this->assertSame(route('pasantias.show', $pasantia->id), $filas['pasantia']['href']);
        $this->assertSame(route('pps-servicio-social.show', $pps->id), $filas['pps']['href']);
        // «Aprobado» no encajaba en ninguna tarjeta y el PPS autorizado no se contaba.
        $this->assertSame(1, $resumen['en_curso']);
        $this->assertSame(1, $resumen['borrador']);
    }

    public function test_requiere_tu_atencion_ve_devoluciones_fuera_de_los_visibles(): void
    {
        $devuelto = $this->pps();
        $this->ponerEstado($devuelto, 'Rechazado', PpsServicioSocial::class);
        $devuelto->forceFill(['created_at' => now()->subYear()])->saveQuietly();

        foreach (range(1, 3) as $i) {
            $this->pps();
        }

        $servicio = app(MisFormulariosService::class);

        $this->assertNotContains($devuelto->codigo_registro, $servicio->para($this->empleado->id, $this->usuario->id, 2)->pluck('codigo'));
        $this->assertSame([$devuelto->codigo_registro], $servicio->porSubsanar($this->empleado->id, $this->usuario->id)->pluck('codigo')->all());
    }

    public function test_la_salud_del_flujo_no_pinta_como_agil_una_etapa_con_pocos_datos(): void
    {
        $etapas = [
            ['etiqueta' => 'Etapa nueva', 'valor' => 0.0, 'firmas' => 2, 'porcentaje' => 0],
            ['etiqueta' => 'Etapa con historia', 'valor' => 40.0, 'firmas' => 9, 'porcentaje' => 100],
        ];

        $html = Blade::render('<x-dashboard.salud-flujo :etapas="$etapas" />', compact('etapas'));

        $this->assertStringContainsString('aún no es representativo', $html);
        $this->assertStringContainsString('poco representativo', $html);
        $this->assertStringContainsString('bg-slate-300', $html);
        $this->assertStringContainsString('bg-red-500', $html);
    }

    private function pps(): PpsServicioSocial
    {
        return PpsServicioSocial::create([
            'codigo_registro' => 'PPS-'.uniqid(),
            'facultad_centro' => 'Facultad de prueba',
            'carrera' => 'Carrera de prueba',
            'numero_cuenta' => '2024'.random_int(10000000, 99999999),
            'nombre_estudiante' => 'Estudiante PPS',
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
    }

    private function ponerEstado($modelo, string $estado, string $tipo): void
    {
        EstadoProyecto::withoutEvents(fn () => EstadoProyecto::create([
            'estadoable_type' => $tipo,
            'estadoable_id' => $modelo->id,
            'empleado_id' => $this->empleado->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => $estado])->id,
            'fecha' => now(),
            'es_actual' => true,
        ]));
    }
}
