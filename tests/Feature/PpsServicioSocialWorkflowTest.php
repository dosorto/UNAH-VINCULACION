<?php

namespace Tests\Feature;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Services\PpsServicioSocial\PpsDocumentoGenerator;
use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PpsServicioSocialWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    public function test_envio_inicia_en_primera_etapa_y_crea_firmas(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);

        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $this->assertSame('enviado', $registro->estado);
        $this->assertNotNull($registro->flujo_aprobacion_id);
        $this->assertNotNull($registro->etapa_actual_id);
        $this->assertSame($ctx['etapas'][0]->id, $registro->etapa_actual_id);
        $this->assertSame($ctx['flujo']->id, $registro->flujo_aprobacion_id);

        $firmas = $registro->firmasDeEtapa()->orderBy('orden_revision')->get();
        $this->assertCount(2, $firmas);
        $this->assertSame([1, 2], $firmas->pluck('orden_revision')->all());
        $this->assertSame('Pendiente', $firmas->first()->estado_revision);

        Mail::assertQueued(EtapaFlujoPendiente::class);
    }

    public function test_borrador_incompleto_se_puede_guardar(): void
    {
        $usuario = User::factory()->create();

        \Livewire\Livewire::actingAs($usuario)
            ->test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->call('guardarBorrador')
            ->assertHasNoErrors();

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();

        $this->assertSame(0, $registro->total_horas);
        $this->assertSame('1900-01-01', $registro->fecha_inicio->format('Y-m-d'));
        $this->assertSame('1900-01-01', $registro->fecha_finalizacion->format('Y-m-d'));
    }

    public function test_generacion_valida_de_solicitud_reutiliza_datos_del_formulario(): void
    {
        $ctx = $this->contexto();
        $documento = app(PpsDocumentoGenerator::class)->generarSolicitud($ctx['registro'], $ctx['usuario']->id);

        $this->assertSame(PpsDocumentoGenerator::SOLICITUD, $documento->tipo);
        Storage::disk('local')->assertExists($documento->archivo);
    }

    public function test_solicitud_se_bloquea_con_mensaje_si_falta_un_dato(): void
    {
        $ctx = $this->contexto();
        $ctx['registro']->update(['cargo_jefe_directo' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cargo del destinatario de la empresa');

        app(PpsDocumentoGenerator::class)->generarSolicitud($ctx['registro']->fresh(), $ctx['usuario']->id);
    }

    public function test_generacion_valida_de_autorizacion_exige_firma_del_coordinador(): void
    {
        $ctx = $this->contexto();
        $documento = app(PpsDocumentoGenerator::class)->generarAutorizacion($ctx['registro'], $ctx['usuario']->id);

        $this->assertSame(PpsDocumentoGenerator::AUTORIZACION, $documento->tipo);
        Storage::disk('local')->assertExists($documento->archivo);
    }

    public function test_autorizacion_se_bloquea_si_falta_fecha_de_inicio(): void
    {
        $ctx = $this->contexto();
        $ctx['registro']->update(['fecha_inicio' => '1900-01-01']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fecha de inicio');

        app(PpsDocumentoGenerator::class)->generarAutorizacion($ctx['registro']->fresh(), $ctx['usuario']->id);
    }

    public function test_datos_territoriales_jefe_supervisor_jornada_horas_y_adjuntos_pueden_quedar_vacios(): void
    {
        $ctx = $this->contexto();
        $ctx['registro']->update([
            'departamento' => null,
            'municipio' => null,
            'nombre_jefe_directo' => null,
            'cargo_jefe_directo' => null,
            'nombre_docente_supervisor' => null,
            'jornada_laboral_docente' => null,
            'horas_teletrabajo' => null,
            'archivo_carta_formalizacion' => null,
            'archivo_convenio_marco' => null,
        ]);

        $missing = PpsDocumentoRequirements::missing(
            $ctx['registro']->fresh(),
            PpsDocumentoGenerator::AUTORIZACION
        );

        $this->assertArrayNotHasKey('departamento', $missing);
        $this->assertArrayNotHasKey('nombre_jefe_directo', $missing);
        $this->assertArrayNotHasKey('jornada_laboral_docente', $missing);
        $this->assertArrayNotHasKey('horas_teletrabajo', $missing);
        $this->assertArrayNotHasKey('archivo_carta_formalizacion', $missing);
    }

    public function test_aprobacion_avanza_a_siguiente_etapa(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);

        $this->assertSame($ctx['etapas'][1]->id, $registro->etapa_actual_id);
        $this->assertSame('enviado', $registro->estado);

        Mail::assertQueued(
            EtapaFlujoPendiente::class,
            fn (EtapaFlujoPendiente $mail) => $mail->etapa->id === $ctx['etapas'][1]->id
        );
    }

    public function test_aprobacion_final_cambia_estado_a_aprobado(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);
        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);

        $this->assertSame('aprobado', $registro->estado);
        $this->assertNotNull($registro->fecha_revision);
    }

    public function test_rechazo_cambia_estado_a_rechazado(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $registro = $service->rechazar($registro, 'Documentación incompleta.', $ctx['usuario']->id);

        $this->assertSame('rechazado', $registro->estado);
        $this->assertNotNull($registro->fecha_revision);
        $this->assertSame('Documentación incompleta.', $registro->motivo_rechazo);

        $this->assertDatabaseHas('estado_proyecto', [
            'estadoable_type' => PpsServicioSocial::class,
            'estadoable_id' => $registro->id,
            'comentario' => 'Documentación incompleta.',
        ]);

        $firma = $registro->firmasDeEtapa()->first();
        $this->assertSame('Rechazado', $firma->estado_revision);
    }

    public function test_iniciar_subsanacion_devuelve_a_borrador(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $registro = $service->rechazar($registro, 'Corrija.', $ctx['usuario']->id);

        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);

        $this->assertSame('borrador', $registro->estado);
        $this->assertDatabaseHas('estado_proyecto', [
            'estadoable_type' => PpsServicioSocial::class,
            'estadoable_id' => $registro->id,
            'comentario' => 'Inicio de subsanación.',
        ]);
    }

    public function test_reenvio_despues_de_subsanacion_crea_nuevo_ciclo(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $registro = $service->rechazar($registro, 'Corrija.', $ctx['usuario']->id);
        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);

        $reenviado = $service->enviarARevision($registro, $ctx['usuario']->id);

        $this->assertSame('enviado', $reenviado->estado);
        $this->assertSame($ctx['etapas'][0]->id, $reenviado->etapa_actual_id);
        $this->assertNull($reenviado->motivo_rechazo);

        $firmas = $reenviado->firmasDeEtapa()
            ->where('revision_ciclo', 2)
            ->orderBy('orden_revision')
            ->get();
        $this->assertCount(2, $firmas);
        $this->assertSame([1, 2], $firmas->pluck('orden_revision')->all());
    }

    public function test_reenvio_desde_segunda_etapa_no_recrea_la_primera_y_regresa_al_revisor(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);
        $registro = $service->rechazar($registro, 'Corrija la segunda etapa.', $ctx['usuario']->id);
        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);

        Mail::fake();
        $reenviado = $service->enviarARevision($registro, $ctx['usuario']->id);

        $firmas = $reenviado->firmasDeEtapa()
            ->where('revision_ciclo', 2)
            ->orderBy('orden_revision')
            ->get();

        $this->assertCount(1, $firmas);
        $this->assertSame($ctx['etapas'][1]->id, $firmas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($ctx['usuario']->id, $firmas->first()->responsable_usuario_id);
        $this->assertSame($ctx['etapas'][1]->id, $reenviado->etapa_actual_id);
        Mail::assertQueued(EtapaFlujoPendiente::class, 1);
    }

    public function test_reenvio_bloquea_revisor_invalido_y_acepta_reemplazo(): void
    {
        $ctx = $this->contexto();
        $adminRole = Role::findOrCreate('admin', 'web');
        $revisor = User::factory()->create(['active_role_id' => $adminRole->id]);
        $revisor->assignRole($adminRole);
        Empleado::create([
            'nombre_completo' => 'Revisor '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $revisor->id,
            'tipo_empleado' => 'docente',
        ]);
        $ctx['etapas']->each->update(['usuario_responsable_id' => $revisor->id]);
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $registro = $service->rechazar($registro, 'Corrija.', $revisor->id, $revisor);
        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);
        $revisor->delete();

        try {
            $service->enviarARevision($registro, $ctx['usuario']->id);
            $this->fail('El reenvío debía bloquearse sin un reemplazo elegible.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('seleccione un reemplazo válido', $exception->getMessage());
        }

        $reemplazo = User::factory()->create(['active_role_id' => $adminRole->id]);
        $reemplazo->assignRole($adminRole);
        $empleado = Empleado::create([
            'nombre_completo' => 'Reemplazo '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $reemplazo->id,
            'tipo_empleado' => 'docente',
        ]);
        $reemplazos = $ctx['etapas']->mapWithKeys(fn ($etapa) => [(int) $etapa->id => $reemplazo->id])->all();

        $reenviado = $service->enviarARevision($registro, $ctx['usuario']->id, $reemplazos);

        $this->assertSame($empleado->id, $reenviado->firmasDeEtapa()->where('revision_ciclo', 2)->value('empleado_id'));
    }

    public function test_no_permite_enviar_si_no_es_borrador(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Solo los registros en estado borrador');
        $service->enviarARevision($registro, $ctx['usuario']->id);
    }

    public function test_no_permite_subsanar_si_no_es_rechazado(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Solo los registros rechazados');
        $service->iniciarSubsanacion($registro, $ctx['usuario']->id);
    }

    public function test_no_permite_aprobar_a_usuario_sin_permiso(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $otro = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El usuario no tiene permisos');
        $service->aprobarEtapa($registro, $otro->id, $otro);
    }

    public function test_notificacion_enviada_en_envio_y_aprobacion(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);

        Mail::fake();
        $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        Mail::assertQueued(EtapaFlujoPendiente::class, 1);

        Mail::fake();
        $registro = $ctx['registro']->fresh();
        $service->aprobarEtapa($registro, $ctx['usuario']->id);
        Mail::assertQueued(EtapaFlujoPendiente::class, 1);
    }

    public function test_rechazo_con_motivo_vacio_lanza_error(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El motivo de rechazo es obligatorio');
        $service->rechazar($registro, '', $ctx['usuario']->id);
    }

    public function test_estados_accesor_reflejan_flujo(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);

        $this->assertSame('borrador', $ctx['registro']->estado);

        $enviado = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $this->assertSame('enviado', $enviado->estado);

        $enviado = $service->aprobarEtapa($enviado, $ctx['usuario']->id);
        $this->assertSame('enviado', $enviado->estado);

        $aprobado = $service->aprobarEtapa($enviado, $ctx['usuario']->id);
        $this->assertSame('aprobado', $aprobado->estado);
    }

    private function contexto(): array
    {
        $usuario = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $usuario->assignRole($adminRole);
        $usuario->update(['active_role_id' => $adminRole->id]);

        $empleado = Empleado::create([
            'nombre_completo' => 'Docente '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $usuario->id,
            'tipo_empleado' => 'docente',
        ]);
        FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'tipo' => 'firma',
            'ruta_storage' => public_path('images/logo_nuevo.png'),
            'estado' => true,
        ]);

        $flujo = FlujoAprobacion::create([
            'codigo' => 'PPS_FLUJO_'.uniqid(),
            'nombre' => 'Flujo PPS/SS Test',
            'proceso' => PpsServicioSocial::PROCESO_FLUJO,
            'codigo_formulario' => 'FORM-DVUS-014',
            'activo' => true,
        ]);

        TipoEstado::firstOrCreate(['nombre' => 'Borrador']);
        TipoEstado::firstOrCreate(['nombre' => 'Aprobado']);
        TipoEstado::firstOrCreate(['nombre' => 'Rechazado']);

        $etapas = collect();
        foreach (range(1, 2) as $orden) {
            $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado etapa '.$orden.'_'.uniqid()]);
            $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo '.$orden.'_'.uniqid()]);
            $cargo = CargoFirma::create([
                'descripcion' => 'Proyecto',
                'tipo_cargo_firma_id' => $tipoCargo->id,
                'tipo_estado_id' => $estado->id,
            ]);
            $etapas->push(FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'PPS_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => $orden === 1 ? 'Coordinador de la carrera' : 'Docente supervisor',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => $cargo->id,
                'usuario_responsable_id' => $usuario->id,
                'activo' => true,
            ]));
        }

        $registro = PpsServicioSocial::create([
            'codigo_registro' => 'PPS-TEST-'.uniqid(),
            'facultad_centro' => 'Facultad de Test',
            'carrera' => 'Test Carrera',
            'numero_cuenta' => '2024'.random_int(10000000, 99999999),
            'nombre_estudiante' => 'Estudiante Test',
            'celular_estudiante' => '99999999',
            'correo_institucional' => 'test@unah.edu.hn',
            'tipo_pps_ss' => 'Practica Profesional Supervisada',
            'fecha_inicio' => now()->toDateString(),
            'fecha_finalizacion' => now()->addMonths(6)->toDateString(),
            'tipo_instrumento' => 'Carta de Formalización',
            'territorio_ejecucion' => 'Nacional',
            'modalidad_ejecucion' => 'Presencial',
            'nombre_institucion' => 'Empresa Test S.A.',
            'nombre_jefe_directo' => 'Jefe Test',
            'cargo_jefe_directo' => 'Jefe de Recursos Humanos',
            'nombre_docente_supervisor' => 'Docente Test',
            'total_horas' => 120,
            'created_by' => $usuario->id,
        ]);

        return compact('usuario', 'empleado', 'flujo', 'etapas', 'registro');
    }
}
