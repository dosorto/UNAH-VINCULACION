<?php

namespace Tests\Feature;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\TipoEstado;
use App\Models\Estado\EstadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Integraciones\IntegracionApiService;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Services\PpsServicioSocial\PpsDocumentoGenerator;
use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\Support\SimulaLibreOffice;
use Tests\TestCase;

class PpsServicioSocialWorkflowTest extends TestCase
{
    use DatabaseTransactions;
    use SimulaLibreOffice;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->simularLibreOffice();
    }

    public function test_creador_puede_eliminar_su_borrador_pps_y_se_registra_la_auditoria(): void
    {
        $contexto = $this->contexto();
        $usuario = $contexto['usuario'];
        $this->actingAs($usuario);
        $registro = $contexto['registro'];

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\ShowPpsServicioSocial::class, ['id' => $registro->id])
            ->call('eliminarBorrador');

        $this->assertSoftDeleted('pps_servicio_social', ['id' => $registro->id]);
        $this->assertNull(PpsServicioSocial::find($registro->id));
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => PpsServicioSocial::class,
            'subject_id' => $registro->id,
            'description' => 'Borrador eliminado lógicamente',
        ]);
    }

    public function test_otro_usuario_no_puede_eliminar_borrador_pps(): void
    {
        $contexto = $this->contexto();
        $otro = User::factory()->create();
        $registro = $contexto['registro'];

        $this->actingAs($otro);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(\App\Livewire\Proyectos\Vinculacion\ShowPpsServicioSocial::class, ['id' => $registro->id])
            ->call('eliminarBorrador');

        $this->assertDatabaseHas('pps_servicio_social', ['id' => $registro->id, 'deleted_at' => null]);
    }

    /** @dataProvider estados_no_eliminables */
    public function test_no_puede_eliminar_pps_fuera_de_borrador(string $estado): void
    {
        $usuario = User::factory()->make(['id' => 77]);
        $registro = new PpsServicioSocial(['created_by' => $usuario->id]);
        $estadoActual = new EstadoProyecto();
        $tipoEstado = new TipoEstado();
        $tipoEstado->nombre = $estado;
        $estadoActual->setRelation('tipoestado', $tipoEstado);
        $registro->setRelation('estadoActual', $estadoActual);

        $this->assertFalse($registro->puedeEliminarBorrador($usuario->id));
    }

    public static function estados_no_eliminables(): array
    {
        return [['en_revision'], ['aprobado'], ['rechazado'], ['subsanacion'], ['cancelado'], ['finalizado']];
    }

    public function test_busca_estudiante_por_cuenta_y_autocompleta_datos_institucionales(): void
    {
        $api = Mockery::mock(IntegracionApiService::class);
        $api->shouldReceive('buscarEstudiantePorCuenta')
            ->once()
            ->with('20240001')
            ->andReturn([
                'ok' => true,
                'datos' => [
                    'numero_cuenta' => '20240001',
                    'nombre_completo' => 'Estudiante API',
                    'correo_institucional' => 'estudiante@unah.edu.hn',
                ],
            ]);
        $this->app->instance(IntegracionApiService::class, $api);

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('numero_cuenta', '20240001')
            ->set('estudiante_celular', 'celular-manual')
            ->call('buscarEstudiante')
            ->assertSet('estudianteConsultado', true)
            ->assertSet('numero_cuenta', '20240001')
            ->assertSet('estudiante_nombre_completo', 'Estudiante API')
            ->assertSet('estudiante_correo_institucional', 'estudiante@unah.edu.hn')
            ->assertSet('estudiante_celular', 'celular-manual');
    }

    public function test_permite_avanzar_con_datos_manuales_mientras_la_api_no_esta_disponible(): void
    {
        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('facultad_centro_id', \App\Models\UnidadAcademica\FacultadCentro::query()->value('id'))
            ->set('carrera_id', \App\Models\UnidadAcademica\Carrera::query()->value('id'))
            ->set('tipo_pps_ss', 'Practica Profesional Supervisada')
            ->set('total_horas', '800')
            ->set('numero_cuenta', '20249999')
            ->set('estudiante_nombre_completo', 'Estudiante capturado manualmente')
            ->set('estudiante_celular', '99999999')
            ->set('estudiante_correo_institucional', 'manual@unah.edu.hn')
            ->set('estudiante_correo_personal', 'manual@example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasNoErrors();
    }

    public function test_sugiere_fecha_finalizacion_cinco_meses_despues_del_inicio(): void
    {
        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('fecha_inicio', '2026-01-15')
            ->assertSet('fecha_finalizacion', '2026-06-15');
    }

    public function test_no_sobrescribe_fecha_finalizacion_manual(): void
    {
        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('fecha_finalizacion', '2026-09-30')
            ->set('fecha_inicio', '2026-01-15')
            ->assertSet('fecha_finalizacion', '2026-09-30');
    }

    public function test_estudiante_no_encontrado_no_sobrescribe_datos_actuales(): void
    {
        $api = Mockery::mock(IntegracionApiService::class);
        $api->shouldReceive('buscarEstudiantePorCuenta')
            ->once()
            ->andReturn(['ok' => false, 'mensaje' => 'No se encontró el estudiante.']);
        $this->app->instance(IntegracionApiService::class, $api);

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('numero_cuenta', '20249999')
            ->set('estudiante_nombre_completo', 'Nombre manual')
            ->set('estudiante_correo_institucional', 'manual@unah.edu.hn')
            ->call('buscarEstudiante')
            ->assertSet('numero_cuenta', '20249999')
            ->assertSet('estudiante_nombre_completo', 'Nombre manual')
            ->assertSet('estudiante_correo_institucional', 'manual@unah.edu.hn')
            ->assertHasErrors(['numero_cuenta']);
    }

    public function test_error_de_api_no_sobrescribe_datos_actuales(): void
    {
        $api = Mockery::mock(IntegracionApiService::class);
        $api->shouldReceive('buscarEstudiantePorCuenta')
            ->once()
            ->andThrow(new \RuntimeException('API no disponible'));
        $this->app->instance(IntegracionApiService::class, $api);

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('numero_cuenta', '20249998')
            ->set('estudiante_nombre_completo', 'Otro nombre manual')
            ->set('estudiante_correo_institucional', 'otro@unah.edu.hn')
            ->call('buscarEstudiante')
            ->assertSet('numero_cuenta', '20249998')
            ->assertSet('estudiante_nombre_completo', 'Otro nombre manual')
            ->assertSet('estudiante_correo_institucional', 'otro@unah.edu.hn')
            ->assertHasErrors(['numero_cuenta']);
    }

    public function test_guarda_y_recarga_los_datos_del_estudiante_obtenidos(): void
    {
        $usuario = User::factory()->create();
        $api = Mockery::mock(IntegracionApiService::class);
        $api->shouldReceive('buscarEstudiantePorCuenta')
            ->once()
            ->andReturn([
                'ok' => true,
                'datos' => [
                    'numero_cuenta' => '20240002',
                    'nombre_completo' => 'Estudiante Persistido',
                    'correo_institucional' => 'persistido@unah.edu.hn',
                ],
            ]);
        $this->app->instance(IntegracionApiService::class, $api);

        $componente = Livewire::actingAs($usuario)
            ->test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('numero_cuenta', '20240002')
            ->call('buscarEstudiante')
            ->call('guardarBorrador');

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $this->assertSame('20240002', $registro->numero_cuenta);
        $this->assertSame('Estudiante Persistido', $registro->nombre_estudiante);
        $this->assertSame('persistido@unah.edu.hn', $registro->correo_institucional);

        Livewire::actingAs($usuario)
            ->test(\App\Livewire\Proyectos\Vinculacion\EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('numero_cuenta', '20240002')
            ->assertSet('estudiante_nombre_completo', 'Estudiante Persistido')
            ->assertSet('estudiante_correo_institucional', 'persistido@unah.edu.hn');
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
        // Se genera antes del envío, sin coordinador del flujo: la firma quien llena el formulario.
        $documento = app(PpsDocumentoGenerator::class)->generarSolicitud($ctx['registro'], $ctx['usuario']->id);

        $this->assertSame(PpsDocumentoGenerator::SOLICITUD, $documento->tipo);
        $this->assertSame(1, $documento->version);
        Storage::disk('local')->assertExists($documento->archivo);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($documento->archivo));

        // El visor del formulario lo pide en línea; el enlace normal lo descarga.
        $this->actingAs($ctx['usuario']);
        request()->query->set('ver', '1');
        $enLinea = app(\App\Http\Controllers\Proyectos\Vinculacion\PpsDocumentoGeneradoController::class)($documento);
        $this->assertStringStartsWith('inline;', $enLinea->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $enLinea->headers->get('Content-Type'));
        request()->query->remove('ver');
        $descarga = app(\App\Http\Controllers\Proyectos\Vinculacion\PpsDocumentoGeneradoController::class)($documento);
        $this->assertStringStartsWith('attachment;', $descarga->headers->get('Content-Disposition'));

        // Al enviar a revisión no se genera otra versión si ya existe.
        app(PpsServicioSocialWorkflowService::class)->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $this->assertSame(1, $ctx['registro']->documentosGenerados()->where('tipo', PpsDocumentoGenerator::SOLICITUD)->count());
    }

    public function test_la_solicitud_se_redacta_para_el_destinatario_y_la_firma_quien_llena_el_formulario(): void
    {
        $ctx = $this->contexto();
        // La carta sale de la plantilla de Word: se revisa el DOCX ya llenado que convierte LibreOffice.
        $docx = sys_get_temp_dir().'/solicitud-'.uniqid().'.docx';
        copy(config('documents.solicitud_practica_pps_template'), $docx);
        app(\App\Services\PpsServicioSocial\PpsSolicitudPracticaDocumento::class)->llenar($docx, $ctx['registro']->fresh(), [
            'nombre' => $ctx['empleado']->nombre_completo,
            'cargo' => PpsDocumentoGenerator::cargoFirmante($ctx['empleado']->sexo),
            'firma' => PpsDocumentoGenerator::imagenFirma($ctx['empleado']->fresh('firma')),
        ]);
        $zip = new \ZipArchive;
        $zip->open($docx);
        $texto = html_entity_decode(strip_tags(str_replace('</w:p>', "\n", (string) $zip->getFromName('word/document.xml'))));
        $pie = html_entity_decode(strip_tags(str_replace('</w:p>', "\n", (string) $zip->getFromName('word/footer1.xml'))));
        $encabezado = (string) $zip->getFromName('word/header2.xml');
        $firmada = str_contains((string) $zip->getFromName('word/document.xml'), '<w:drawing>');
        $zip->close();
        unlink($docx);

        $this->assertStringNotContainsString('{{', $texto.$pie);
        // La firma registrada del coordinador (la del contexto) va sobre su nombre.
        $this->assertTrue($firmada);
        // Pie con el lema y la línea del campus; sin el número de página del FORM-DVUS-018.
        $this->assertStringContainsString('“La Educación es la Primera Necesidad de La República”', $pie);
        $this->assertStringContainsString('Universidad Nacional Autónoma de Honduras | CU | Tegucigalpa M.D.C., Honduras C.A. | www.unah.edu.hn', $pie);
        $this->assertStringNotContainsString('PAGE', $encabezado);
        foreach ([
            "LICENCIADA\nMARÍA HELENA MEJÍA\nCOORDINADORA DE RECLUTAMIENTO\nEMPRESA TEST S.A.\nPresente",
            'Estimada Licenciada:',
            'Choluteca, ',
            'desea realizar la práctica profesional supervisada de 120 horas en su institución',
            'válida solo en modalidad presencial.',
            'NOMBRE DEL ALUMNO: ESTUDIANTE TEST',
            'máximo 40 horas semanales',
            $ctx['empleado']->nombre_completo."\nCoordinador Académico\nTest Carrera, Facultad de Test",
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $texto);
        }
    }

    public function test_solicitud_se_bloquea_con_mensaje_si_falta_un_dato(): void
    {
        $ctx = $this->contexto();
        $ctx['registro']->update(['destinatario_cargo' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cargo del destinatario de la solicitud');

        app(PpsDocumentoGenerator::class)->generarSolicitud($ctx['registro']->fresh(), $ctx['usuario']->id);
    }

    public function test_al_enviar_a_firmar_se_genera_la_autorizacion_y_cada_reenvio_crea_otra_version(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);
        $autorizaciones = fn () => $ctx['registro']->documentosGenerados()->where('tipo', PpsDocumentoGenerator::AUTORIZACION)->orderBy('version')->get();

        $this->assertCount(0, $autorizaciones());
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);

        $this->assertCount(1, $autorizaciones());
        $documento = $autorizaciones()->first();
        $this->assertSame(1, $documento->version);
        $this->assertSame($ctx['usuario']->id, (int) $documento->generado_por);
        Storage::disk('local')->assertExists($documento->archivo);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($documento->archivo));

        // Tras una subsanación, el reenvío genera la autorización con los datos corregidos.
        $registro = $service->rechazar($registro, 'Corrija las fechas.', $ctx['usuario']->id);
        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);
        $registro = $service->enviarARevision($registro, $ctx['usuario']->id);
        $this->assertSame([1, 2], $autorizaciones()->pluck('version')->all());

        // La aprobación final ya no genera otra.
        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);
        $registro = $service->aprobarEtapa($registro, $ctx['usuario']->id);
        $this->assertSame('aprobado', $registro->estado);
        $this->assertCount(2, $autorizaciones());
    }

    public function test_la_autorizacion_tiene_el_formato_del_ejemplo_y_la_firma_quien_llena_el_formulario(): void
    {
        $ctx = $this->contexto();
        $this->travelTo(now()->setDate(2026, 2, 24));
        $ctx['registro']->update(['fecha_inicio' => '2026-02-27', 'fecha_finalizacion' => '2026-07-25']);
        $docx = sys_get_temp_dir().'/autorizacion-'.uniqid().'.docx';
        copy(config('documents.autorizacion_pps_template'), $docx);
        app(\App\Services\PpsServicioSocial\PpsAutorizacionDocumento::class)->llenar($docx, $ctx['registro']->fresh(), [
            'nombre' => $ctx['empleado']->nombre_completo,
            'cargo' => PpsDocumentoGenerator::cargoFirmante('Femenino'),
            'sexo' => 'Femenino',
            'firma' => PpsDocumentoGenerator::imagenFirma($ctx['empleado']->fresh('firma')),
        ]);
        $zip = new \ZipArchive;
        $zip->open($docx);
        $cuerpo = (string) $zip->getFromName('word/document.xml');
        $texto = html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $cuerpo)));
        $pie = html_entity_decode(strip_tags(str_replace('</w:p>', "\n", (string) $zip->getFromName('word/footer1.xml'))));
        $zip->close();
        unlink($docx);

        $this->assertStringNotContainsString('{{', $texto.$pie);
        $this->assertStringContainsString('<w:drawing>', $cuerpo);
        $this->assertStringContainsString('“La Educación es la Primera Necesidad de La República”', $pie);
        foreach ([
            '“Año Académico 2026 María Elena Bottazzi”',
            'Choluteca, 24 de febrero de 2026',
            'AUTORIZACIÓN DE PRÁCTICA PROFESIONAL',
            'La suscrita Coordinadora de la Carrera de Test Carrera de la Universidad Nacional Autónoma de Honduras en Facultad de Test; por este medio AUTORIZA al estudiante ESTUDIANTE TEST con número de cuenta '.$ctx['registro']->numero_cuenta,
            'para que realice la Práctica Profesional Supervisada de 120 horas (máximo 40 horas semanales), en la empresa Empresa Test S.A.,',
            'la cual será válida solo en modalidad presencial, iniciando el 27 de febrero del año 2026 y con fecha de finalización el 25 de julio del año 2026.',
            'Y para los fines que el interesado convenga, firmo la presente en Choluteca, a los 24 días del mes de febrero del año 2026.',
            'Se adjunta oficio de Supervisión de Práctica',
            $ctx['empleado']->nombre_completo."\nCoordinadora Académica\nTest Carrera, Facultad de Test",
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $texto);
        }
    }

    public function test_al_enviar_se_piden_tambien_los_datos_de_la_autorizacion(): void
    {
        $ctx = $this->contexto();
        $ctx['registro']->update(['fecha_inicio' => PpsDocumentoRequirements::BORRADOR_FECHA]);

        $this->assertContains('fecha de inicio', $ctx['registro']->fresh()->camposFaltantesParaEnvio());
    }

    public function test_la_pantalla_muestra_la_ficha_y_sus_adjuntos_en_el_visor(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pps-servicio-social/anexos/carta.docx', 'carta');
        $ctx = $this->contexto();
        $ctx['registro']->update([
            'adjunta_carta_formalizacion' => true,
            'archivo_carta_formalizacion' => 'pps-servicio-social/anexos/carta.docx',
        ]);
        $registro = app(PpsServicioSocialWorkflowService::class)->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $autorizacion = $registro->documentosGenerados()->where('tipo', PpsDocumentoGenerator::AUTORIZACION)->firstOrFail();

        Livewire::actingAs($ctx['usuario'])
            ->test(\App\Livewire\Proyectos\Vinculacion\ShowPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSeeInOrder(['Ficha', 'Adjunto 1 · Solicitud de práctica', 'Adjunto 2 · Carta de formalización', 'Adjunto 3 · Autorización de PPS'])
            ->assertSee(route('pps-servicio-social.pdf', ['id' => $registro->id, 'ver' => 1, 'v' => $registro->updated_at->timestamp]))
            ->assertSee(route('pps-servicio-social.anexo', ['id' => $registro->id, 'tipo' => 'carta-formalizacion']))
            ->assertSee(route('pps-servicio-social.documento-generado', ['documento' => $autorizacion->id, 'ver' => 1]))
            ->assertSee('Estado:')
            ->assertSee('En revisión')
            ->assertSeeInOrder(['Coordinador de la carrera', 'Docente supervisor'])
            ->assertSee('Historial de movimientos')
            ->assertDontSee('Datos registrados para revisión');
    }

    public function test_la_ficha_se_ve_en_el_visor_o_se_descarga_y_la_consultan_los_roles_de_historial(): void
    {
        $ctx = $this->contexto();
        $controlador = app(\App\Http\Controllers\Proyectos\Vinculacion\PpsServicioSocialPdfController::class);
        $this->beforeApplicationDestroyed(fn () => \Illuminate\Support\Facades\File::deleteDirectory(storage_path('app/generated/form-dvus-014/'.$ctx['registro']->id)));

        $this->actingAs($ctx['usuario']);
        request()->query->set('ver', '1');
        $enLinea = $controlador($ctx['registro']->id, app(\App\Services\PpsServicioSocial\FormDvus014DocumentService::class));
        $this->assertStringStartsWith('inline;', $enLinea->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $enLinea->headers->get('Content-Type'));
        request()->query->remove('ver');
        $descarga = $controlador($ctx['registro']->id, app(\App\Services\PpsServicioSocial\FormDvus014DocumentService::class));
        $this->assertStringStartsWith('attachment;', $descarga->headers->get('Content-Disposition'));

        // Quien puede ver el registro por su rol (historial) también ve la ficha.
        $historial = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'proyectos.historial', 'guard_name' => 'web']);
        $rol = Role::firstOrCreate(['name' => 'Historial PPS '.uniqid(), 'guard_name' => 'web']);
        $rol->givePermissionTo($historial);
        $consulta = User::factory()->create();
        $consulta->assignRole($rol);
        $consulta->update(['active_role_id' => $rol->id]);
        $this->assertTrue($ctx['registro']->puedeConsultarse($consulta->id, $consulta->fresh()));

        $ajeno = User::factory()->create();
        $this->assertFalse($ctx['registro']->puedeConsultarse($ajeno->id, $ajeno));
    }

    public function test_los_anexos_de_word_se_ven_como_pdf_y_se_descargan_originales(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pps-servicio-social/anexos/carta.docx', 'carta en word');
        $ctx = $this->contexto();
        $ctx['registro']->update(['archivo_carta_formalizacion' => 'pps-servicio-social/anexos/carta.docx']);
        $controlador = app(\App\Http\Controllers\Proyectos\Vinculacion\PpsServicioSocialAnexoController::class);
        $servicio = app(\App\Services\PpsServicioSocial\FormDvus014DocumentService::class);
        $this->actingAs($ctx['usuario']);

        $vista = $controlador(request(), $ctx['registro']->id, 'carta-formalizacion', $servicio);
        $this->assertSame('application/pdf', $vista->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline;', $vista->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($vista->getFile()->getPathname()));
        @unlink($vista->getFile()->getPathname());

        $descarga = $controlador(new \Illuminate\Http\Request(['download' => 1]), $ctx['registro']->id, 'carta-formalizacion', $servicio);
        $this->assertStringStartsWith('attachment;', $descarga->headers->get('Content-Disposition'));
        $this->assertStringContainsString('carta.docx', $descarga->headers->get('Content-Disposition'));
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
            'nombre_jefe_directo' => '',
            'cargo_jefe_directo' => null,
            'nombre_docente_supervisor' => '',
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

    public function test_la_fecha_de_registro_es_la_del_primer_envio_y_no_cambia_al_reenviar(): void
    {
        $ctx = $this->contexto();
        $service = app(PpsServicioSocialWorkflowService::class);

        // Mientras es borrador el formulario no tiene fecha de registro.
        $this->assertNull($ctx['registro']->fecha_registro);
        $this->assertNull(\App\Support\PpsServicioSocial\FormDvus014Data::from($ctx['registro'])['fields']['fecha_registro']);

        $this->travelTo(now()->setDate(2026, 3, 2));
        $registro = $service->enviarARevision($ctx['registro'], $ctx['usuario']->id);
        $this->assertSame('2026-03-02', $registro->fecha_registro->format('Y-m-d'));

        $registro = $service->rechazar($registro, 'Corrija.', $ctx['usuario']->id);
        $registro = $service->iniciarSubsanacion($registro, $ctx['usuario']->id);
        $this->travelTo(now()->setDate(2026, 3, 20));
        $reenviado = $service->enviarARevision($registro, $ctx['usuario']->id);

        $this->assertSame('2026-03-20', $reenviado->fecha_envio->format('Y-m-d'));
        $this->assertSame('2026-03-02', $reenviado->fecha_registro->format('Y-m-d'));
        $this->assertSame('2026-03-02', \App\Support\PpsServicioSocial\FormDvus014Data::from($reenviado)['fields']['fecha_registro']->format('Y-m-d'));
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
            'destinatario_tratamiento' => 'Licenciada',
            'destinatario_nombre' => 'María Helena Mejía',
            'destinatario_cargo' => 'Coordinadora de Reclutamiento',
            'solicitud_lugar' => 'Choluteca',
            'nombre_jefe_directo' => 'Jefe Test',
            'cargo_jefe_directo' => 'Jefe de Recursos Humanos',
            'nombre_docente_supervisor' => 'Docente Test',
            'total_horas' => 120,
            'created_by' => $usuario->id,
        ]);

        return compact('usuario', 'empleado', 'flujo', 'etapas', 'registro');
    }
}
