<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Mail\EtapaFlujoPendiente;
use App\Mail\ProyectoEstadoCambiado;
use App\Models\Estado\TipoEstado;
use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\InformeIntermedio\InformeIntermedioProyectoWorkflowService;
use App\Services\Proyecto\ProyectoWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InformeIntermedioWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
    }

    public function test_las_etapas_se_seleccionan_por_proceso_actividad_y_orden_sin_codigos_fijos(): void
    {
        $contexto = $this->contexto();
        $workflow = app(ProyectoWorkflowService::class);

        $this->assertSame([1, 2, 3, 4, 5], $workflow->etapasInscripcion($contexto['proyecto'])->pluck('orden')->all());
        $this->assertSame([4, 5], $workflow->etapasInformeIntermedio($contexto['proyecto'])->pluck('orden')->all());
        $this->assertSame([4, 5], $workflow->etapasCierre($contexto['proyecto'])->pluck('orden')->all());

        $contexto['etapas'][3]->update(['activo' => false]);
        $this->assertSame([5], $workflow->etapasInformeIntermedio($contexto['proyecto']->fresh())->pluck('orden')->all());
        $this->assertSame(5, $workflow->primeraEtapa(
            $contexto['proyecto']->fresh(),
            Proyecto::FLUJO_INFORME_INTERMEDIO
        )->orden);
    }

    public function test_no_habilita_el_informe_antes_de_completar_inscripcion_y_luego_si(): void
    {
        $contexto = $this->contexto(false);
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);

        $this->assertFalse($workflow->estaDisponible($contexto['proyecto'], $contexto['usuario']));

        $this->crearFirmasInscripcionAprobadas($contexto);

        $this->assertTrue($workflow->estaDisponible($contexto['proyecto']->fresh(), $contexto['usuario']));
    }

    public function test_valida_pdf_y_permite_cargar_reemplazar_y_eliminar_solo_en_borrador(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);

        $this->expectException(\RuntimeException::class);
        $workflow->guardarArchivo(
            $contexto['proyecto'],
            UploadedFile::fake()->create('informe.txt', 10, 'text/plain'),
            $contexto['usuario']
        );
    }

    public function test_reemplazo_de_borrador_elimina_el_archivo_anterior(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $primero = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf('uno.pdf'), $contexto['usuario']);
        $pathAnterior = $primero->archivo_pdf;
        $segundo = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf('dos.pdf'), $contexto['usuario']);

        Storage::disk('local')->assertMissing($pathAnterior);
        Storage::disk('local')->assertExists($segundo->archivo_pdf);
        $this->assertSame('dos.pdf', $segundo->nombre_original);

        $workflow->eliminarArchivo($segundo, $contexto['usuario']);
        Storage::disk('local')->assertMissing($segundo->archivo_pdf);
        $this->assertDatabaseMissing('informe_intermedio_proyectos', ['id' => $segundo->id]);
    }

    public function test_envio_inicia_en_primera_etapa_intermedia_y_es_idempotente(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $informe = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $documento = $workflow->enviar($informe, $contexto['usuario']);
        $firmas = $documento->firma_documento()->orderBy('orden_revision')->get();

        $this->assertSame([4, 5], $firmas->pluck('orden_revision')->all());
        $this->assertSame($contexto['etapas'][3]->id, $firmas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame(InformeIntermedioProyecto::ESTADO_EN_REVISION, $informe->fresh()->estado);
        Mail::assertQueued(
            EtapaFlujoPendiente::class,
            fn (EtapaFlujoPendiente $mail): bool => $mail->etapa->id === $contexto['etapas'][3]->id
        );

        try {
            $workflow->enviar($informe->fresh(), $contexto['usuario']);
            $this->fail('El segundo envío debía rechazarse.');
        } catch (\RuntimeException) {
            $this->assertSame(2, $documento->firma_documento()->count());
            $this->assertSame(1, $documento->estado_documento()->count());
        }

        $this->expectException(\RuntimeException::class);
        $workflow->guardarArchivo($contexto['proyecto'], $this->pdf('bloqueado.pdf'), $contexto['usuario']);
    }

    public function test_subsanacion_permite_reemplazar_y_retorna_a_la_misma_etapa(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $informe = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $documento = $workflow->enviar($informe, $contexto['usuario']);
        $primera = $documento->firma_documento()->orderBy('orden_revision')->firstOrFail();

        $this->harness()->rechazarEtapa($primera, $contexto['usuario'], 'Corrija el contenido.');
        $this->assertSame(InformeIntermedioProyecto::ESTADO_SUBSANACION, $informe->fresh()->estado);
        $this->assertSame($primera->flujo_aprobacion_etapa_id, $informe->fresh()->etapa_retorno_id);
        Mail::assertQueued(
            ProyectoEstadoCambiado::class,
            fn (ProyectoEstadoCambiado $mail): bool => str_contains($mail->comentario, 'Corrija el contenido')
        );

        $reemplazado = $workflow->guardarArchivo($contexto['proyecto']->fresh(), $this->pdf('corregido.pdf'), $contexto['usuario']);
        $workflow->enviar($reemplazado, $contexto['usuario']);
        $actual = $documento->fresh()->firma_documento()
            ->where('revision_ciclo', 2)
            ->orderBy('orden_revision')
            ->firstOrFail();

        $this->assertSame($primera->flujo_aprobacion_etapa_id, $actual->flujo_aprobacion_etapa_id);
        $this->assertSame(4, $actual->orden_revision);
    }

    public function test_subsanacion_en_segunda_etapa_no_recrea_la_primera(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $informe = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $documento = $workflow->enviar($informe, $contexto['usuario']);
        $firmas = $documento->firma_documento()->orderBy('orden_revision')->get();
        $this->harness()->aprobarEtapa($firmas[0], $contexto['usuario']);
        $this->harness()->rechazarEtapa($firmas[1]->fresh(), $contexto['usuario'], 'Corrija la etapa final.');

        $corregido = $workflow->guardarArchivo($contexto['proyecto']->fresh(), $this->pdf('corregido-segunda.pdf'), $contexto['usuario']);
        $workflow->enviar($corregido, $contexto['usuario']);
        $nuevoCiclo = $documento->fresh()->firma_documento()
            ->where('revision_ciclo', 2)
            ->orderBy('orden_revision')
            ->get();

        $this->assertCount(1, $nuevoCiclo);
        $this->assertSame($contexto['etapas'][4]->id, $nuevoCiclo->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($contexto['usuario']->id, $nuevoCiclo->first()->responsable_usuario_id);
    }

    public function test_aprobacion_avanza_notifica_el_informe_final_y_no_finaliza_el_proyecto(): void
    {
        $contexto = $this->contexto();
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $informe = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $documento = $workflow->enviar($informe, $contexto['usuario']);
        $firmas = $documento->firma_documento()->orderBy('orden_revision')->get();

        $this->harness()->aprobarEtapa($firmas[0], $contexto['usuario']);
        $this->assertSame($contexto['etapas'][4]->id, $informe->fresh()->etapa_actual_id);
        Mail::assertQueued(
            EtapaFlujoPendiente::class,
            fn (EtapaFlujoPendiente $mail): bool => $mail->etapa->id === $contexto['etapas'][4]->id
        );

        $this->harness()->aprobarEtapa($firmas[1]->fresh(), $contexto['usuario']);
        $this->assertSame(InformeIntermedioProyecto::ESTADO_APROBADO, $informe->fresh()->estado);
        $this->assertSame('En curso', $contexto['proyecto']->fresh()->estado?->tipoestado?->nombre);
        $this->assertTrue($contexto['proyecto']->fresh()->puedeMostrarCierreProyecto($contexto['usuario']));
        Mail::assertQueued(
            ProyectoEstadoCambiado::class,
            fn (ProyectoEstadoCambiado $mail): bool => $mail->nuevoEstado === 'Informe intermedio aprobado'
                && str_contains($mail->comentario, 'Informe Final')
                && $mail->actionUrl === route('proyectos.informe-final', $contexto['proyecto'])
        );

    }

    public function test_no_permite_aprobar_informe_intermedio_sin_firma_activa(): void
    {
        $contexto = $this->contexto(true, false);
        $workflow = app(InformeIntermedioProyectoWorkflowService::class);
        $informe = $workflow->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $documento = $workflow->enviar($informe, $contexto['usuario']);
        $firma = $documento->firma_documento()->orderBy('orden_revision')->firstOrFail();

        try {
            $this->harness()->aprobarEtapa($firma, $contexto['usuario']);
            $this->fail('La aprobación sin firma debió rechazarse.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'No puede aprobar esta etapa porque no tiene una firma registrada. Registre su firma antes de continuar.',
                $exception->getMessage()
            );
        }

        $this->assertSame('Pendiente', $firma->fresh()->estado_revision);
        $this->assertNull($firma->fresh()->firma_id);
        $this->assertSame(1, $documento->estado_documento()->count());
    }

    public function test_pdf_requiere_autenticacion_y_autorizacion(): void
    {
        $contexto = $this->contexto();
        $informe = app(InformeIntermedioProyectoWorkflowService::class)
            ->guardarArchivo($contexto['proyecto'], $this->pdf(), $contexto['usuario']);
        $ajeno = User::factory()->create();

        $this->get(route('informes-intermedios.ver', $informe))->assertRedirect();
        $this->actingAs($ajeno)->get(route('informes-intermedios.descargar', $informe))->assertForbidden();
        $this->actingAs($contexto['usuario'])->get(route('informes-intermedios.ver', $informe))->assertOk();
    }

    public function test_responsable_fijo_y_destinatario_definido_por_emisor_se_validan(): void
    {
        $contexto = $this->contexto();
        $workflow = app(ProyectoWorkflowService::class);
        $etapa = $contexto['etapas'][3];
        $rol = Role::firstOrCreate(['name' => 'Revisor intermedio '.uniqid(), 'guard_name' => 'web']);
        $etapa->update([
            'usuario_responsable_id' => null,
            'rol_revisor_id' => $rol->id,
            'emisor_define_destinatario' => true,
        ]);

        try {
            $workflow->resolverEmpleados($contexto['proyecto']->fresh(), Proyecto::FLUJO_INFORME_INTERMEDIO);
            $this->fail('Debía exigir destinatario.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Debe seleccionar', $exception->getMessage());
        }

        $contexto['usuario']->assignRole($rol);
        $resueltos = $workflow->resolverEmpleados(
            $contexto['proyecto']->fresh(),
            Proyecto::FLUJO_INFORME_INTERMEDIO,
            [$etapa->id => $contexto['usuario']->id]
        );
        $this->assertSame($contexto['empleado']->id, $resueltos[$etapa->id]);

        $etapa->update([
            'emisor_define_destinatario' => false,
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);
        $this->expectException(\RuntimeException::class);
        $workflow->resolverEmpleados($contexto['proyecto']->fresh(), Proyecto::FLUJO_INFORME_INTERMEDIO);
    }

    private function contexto(bool $inscripcionAprobada = true, bool $conFirma = true): array
    {
        $usuario = User::factory()->create();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $usuario->assignRole($admin);
        $empleado = Empleado::create([
            'nombre_completo' => 'Docente '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $usuario->id,
            'tipo_empleado' => 'docente',
        ]);
        if ($conFirma) {
            FirmaSelloEmpleado::create([
                'empleado_id' => $empleado->id,
                'tipo' => 'firma',
                'ruta_storage' => 'firmas/'.uniqid().'.png',
                'estado' => true,
            ]);
        }
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto workflow '.uniqid(),
            'codigo_proyecto' => 'PR-'.uniqid(),
        ]);
        EmpleadoProyecto::create([
            'empleado_id' => $empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
        ]);

        $flujo = FlujoAprobacion::create([
            'codigo' => 'WF-'.uniqid(),
            'nombre' => 'Workflow de informes',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $etapas = collect();

        foreach (range(1, 5) as $orden) {
            $estado = TipoEstado::firstOrCreate(['nombre' => $orden <= 3 ? 'Revisión '.$orden.' '.uniqid() : 'Revisión informe '.$orden.' '.uniqid()]);
            $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo '.$orden.' '.uniqid()]);
            $cargo = CargoFirma::create([
                'descripcion' => 'Proyecto',
                'tipo_cargo_firma_id' => $tipoCargo->id,
                'tipo_estado_id' => $estado->id,
            ]);
            $etapas->push(FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'DINAMICA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa '.$orden,
                'tipo_etapa' => 'APROBACION',
                'cargo_firma_id' => $cargo->id,
                'usuario_responsable_id' => $usuario->id,
                'activo' => true,
                'aplica_inscripcion' => true,
                'aplica_informe_intermedio' => $orden >= 4,
                'aplica_cierre_proyecto' => $orden >= 4,
            ]));
        }

        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);
        $enCurso = TipoEstado::firstOrCreate(['nombre' => 'En curso']);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $enCurso->id,
            'fecha' => now(),
            'comentario' => 'Proyecto operativo.',
            'es_actual' => true,
        ]);

        $contexto = compact('usuario', 'empleado', 'proyecto', 'flujo', 'etapas');

        if ($inscripcionAprobada) {
            $this->crearFirmasInscripcionAprobadas($contexto);
        }

        return $contexto;
    }

    private function crearFirmasInscripcionAprobadas(array $contexto): void
    {
        foreach ($contexto['etapas'] as $etapa) {
            $contexto['proyecto']->guardarFirmaDeEtapa($etapa, $contexto['empleado'], [
                'estado_revision' => 'Aprobado',
                'fecha_firma' => now(),
            ]);
        }
    }

    private function pdf(string $nombre = 'intermedio.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nombre,
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"
        );
    }

    private function harness(): InformeIntermedioWorkflowHarness
    {
        return new InformeIntermedioWorkflowHarness;
    }
}

class InformeIntermedioWorkflowHarness extends ProyectosPorFirmar
{
    public function aprobarEtapa(FirmaProyecto $firma, User $user): FirmaProyecto
    {
        return $this->aprobarFirmaPorEtapa($firma, $user);
    }

    public function rechazarEtapa(FirmaProyecto $firma, User $user, string $comentario): FirmaProyecto
    {
        return $this->rechazarFirmaPorEtapa($firma, $user, $comentario);
    }
}
