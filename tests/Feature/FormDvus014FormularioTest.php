<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial;
use App\Livewire\Proyectos\Vinculacion\EditPpsServicioSocial;
use App\Models\Personal\Empleado;
use App\Models\PpsInstitucion;
use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FORM-DVUS-014 en nueve pasos: primero lo que necesita la solicitud de práctica, luego se genera
 * y después lo demás. Cada paso exige sus campos, la institución sale de su catálogo y el
 * supervisor se elige entre los docentes.
 */
class FormDvus014FormularioTest extends TestCase
{
    use DatabaseTransactions;

    private function formulario()
    {
        return Livewire::test(CreatePpsServicioSocial::class)->set('autoguardadoActivo', false);
    }

    private function institucion(string $nombre = 'Hospital Escuela de Prueba'): PpsInstitucion
    {
        return PpsInstitucion::create([
            'nombre' => $nombre, 'nacionalidad' => 'Nacional', 'pais' => null,
            'tipo' => 'gobierno_nacional', 'sector' => 'servicios_funcion_publicos',
            'direccion' => 'Bulevar Suyapa, Tegucigalpa', 'representante_legal' => 'Ana López',
            'telefono' => '2232-0000', 'correo_rrhh' => 'rrhh@example.com',
        ]);
    }

    private function pais(string $nombre, int $codigo): \App\Models\Demografia\Pais
    {
        return \App\Models\Demografia\Pais::create([
            'nombre' => $nombre, 'gentilicio' => 'de prueba', 'codigo_area' => $codigo,
            'codigo_iso' => 'Z'.$codigo, 'codigo_iso_numerico' => $codigo, 'codigo_iso_alpha_2' => 'Z'.($codigo % 10),
        ]);
    }

    public function test_primero_se_pide_lo_de_la_solicitud_y_despues_lo_demas(): void
    {
        $this->assertSame([
            1 => 'Información general', 2 => 'Estudiante', 3 => 'Institución y destinatario',
            4 => 'Solicitud de práctica', 5 => 'Fechas y alcance', 6 => 'Ubicación y jornada',
            7 => 'Instrumento y jefe directo', 8 => 'Supervisor', 9 => 'Revisión y envío',
        ], CreatePpsServicioSocial::PASOS);

        $this->formulario()
            ->assertSet('totalSteps', 9)
            ->assertSee('Paso 1: Información general')
            ->assertSee('Solicitud de práctica');
    }

    public function test_la_solicitud_se_genera_en_el_paso_4_y_sin_ella_no_se_avanza(): void
    {
        Storage::fake('local');
        $usuario = User::factory()->create();
        Empleado::create([
            'nombre_completo' => 'Coordinadora de Prueba', 'numero_empleado' => '55667788',
            'celular' => '', 'sexo' => 'Femenino', 'user_id' => $usuario->id, 'tipo_empleado' => 'docente',
        ]);
        $institucion = $this->institucion();

        $formulario = Livewire::actingAs($usuario)->test(CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('facultad_centro_id', FacultadCentro::query()->value('id'))
            ->set('carrera_id', Carrera::query()->value('id'))
            ->set('tipo_pps_ss', 'Practica Profesional Supervisada')
            ->set('total_horas', '800')
            ->set('numero_cuenta', '20182300096')
            ->set('estudiante_nombre_completo', 'Erick José Reyes Pineda')
            ->set('estudiante_celular', '9999-0000')
            ->set('estudiante_correo_institucional', 'erick.reyes@unah.hn')
            ->set('estudiante_correo_personal', 'erick@example.com')
            ->set('institucionBuscadaId', (string) $institucion->id)
            ->call('usarInstitucionSeleccionada')
            ->set('destinatario_tratamiento', 'Licenciada')
            ->set('destinatario_nombre', 'María Helena Mejía')
            ->set('destinatario_cargo', 'Coordinadora de Reclutamiento')
            ->set('modalidad_ejecucion', 'Presencial')
            // Con lo necesario para la carta completo se llega a la solicitud, que propone lugar y cargo.
            ->call('goToStep', 4)
            ->assertSet('currentStep', 4)
            ->assertSet('solicitud_firmante_cargo', 'Coordinadora Académica')
            ->assertSee('Coordinadora de Prueba')
            ->assertSee('Generar solicitud');
        $this->assertNotSame('', $formulario->get('solicitud_lugar'));

        // Sin generar la solicitud no se continúa.
        $formulario->call('nextStep')->assertHasErrors('solicitud')->assertSet('currentStep', 4);

        $formulario->call('generarSolicitud')
            ->assertHasNoErrors()
            ->assertSee('Solicitud de práctica · v1')
            ->assertSee('Generar nueva versión')
            ->call('nextStep')
            ->assertSet('currentStep', 5);

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $this->assertSame('María Helena Mejía', $registro->destinatario_nombre);
        $documento = $registro->documentosGenerados()->where('tipo', 'solicitud_practica')->firstOrFail();
        Storage::disk('local')->assertExists($documento->archivo);
    }

    public function test_la_solicitud_no_se_genera_si_faltan_datos_de_los_pasos_anteriores(): void
    {
        $this->formulario()
            ->set('currentStep', 4)
            ->call('generarSolicitud')
            ->assertHasErrors(['facultad_centro_id', 'total_horas'])
            ->assertSet('currentStep', 1);
    }

    public function test_cada_paso_exige_sus_campos_y_el_estado_completo_usa_la_misma_regla(): void
    {
        $formulario = $this->formulario();
        $formulario->call('nextStep')
            ->assertHasErrors(['facultad_centro_id', 'carrera_id', 'tipo_pps_ss', 'total_horas'])
            ->assertHasNoErrors(['fecha_inicio', 'fecha_finalizacion'])
            ->assertSet('currentStep', 1);
        $this->assertFalse($formulario->instance()->isStepComplete(1));

        // Las fechas llegan con la respuesta de la institución (paso 5).
        $formulario->set('currentStep', 5)
            ->set('fecha_inicio', '2026-03-01')->set('fecha_finalizacion', '2026-02-01')
            ->call('nextStep')
            ->assertHasErrors(['fecha_finalizacion' => 'after_or_equal']);
    }

    public function test_las_fechas_de_ejecucion_muestran_las_horas_planificadas_de_lunes_a_viernes(): void
    {
        $componente = new CreatePpsServicioSocial;
        $horas = function (string $inicio, string $fin) use ($componente) {
            $componente->fecha_inicio = $inicio;
            $componente->fecha_finalizacion = $fin;

            return $componente->horasPlanificadas();
        };

        $this->assertSame(80, $horas('2026-03-02', '2026-03-13'));  // lunes a viernes de dos semanas
        $this->assertSame(80, $horas('2026-03-01', '2026-03-14'));  // los fines de semana de los extremos no cuentan
        $this->assertSame(8, $horas('2026-03-04', '2026-03-04'));   // un solo día hábil
        $this->assertSame(0, $horas('2026-03-07', '2026-03-08'));   // solo sábado y domingo
        $this->assertSame(880, $horas('2026-03-02', '2026-07-31')); // 110 días hábiles
        $this->assertNull($horas('2026-03-13', '2026-03-02'));      // fechas invertidas
        $this->assertNull($horas('2026-03-02', ''));

        $this->formulario()
            ->set('currentStep', 5)
            ->set('fecha_inicio', '2026-03-02')
            ->set('fecha_finalizacion', '2026-03-13')
            ->assertSee('Horas planificadas:')
            ->assertSeeHtml('<strong>80</strong>')
            ->assertSee('10 días hábiles de lunes a viernes × 8 horas');
    }

    public function test_la_ubicacion_exige_solo_el_bloque_de_la_modalidad(): void
    {
        $formulario = $this->formulario()->set('currentStep', 6)->set('territorio_ejecucion', 'Nacional');

        $formulario->set('modalidad_ejecucion', 'Presencial')->call('nextStep')
            ->assertHasErrors(['departamento_id', 'municipio_id', 'aldea_ciudad', 'caserio', 'horas_presenciales'])
            ->assertHasNoErrors(['pais_sede_id', 'departamento_sede_id', 'horas_teletrabajo', 'pais']);

        // En Honduras la sede principal se elige del catálogo, como la práctica presencial.
        $formulario->set('modalidad_ejecucion', '100% virtual')->call('nextStep')
            ->assertHasErrors(['departamento_sede_id', 'municipio_sede_id', 'aldea_ciudad_sede_principal', 'horas_teletrabajo'])
            ->assertHasNoErrors(['pais_sede_id', 'departamento_id', 'caserio', 'horas_presenciales']);

        $formulario->set('modalidad_ejecucion', 'Hibrida')->set('territorio_ejecucion', 'Internacional')->call('nextStep')
            ->assertHasErrors(['pais_id', 'pais_sede_id', 'horas_presenciales', 'horas_teletrabajo']);
    }

    public function test_en_una_practica_internacional_el_pais_sale_del_catalogo_con_sus_departamentos(): void
    {
        // País sin departamentos en el catálogo: departamento y municipio se escriben.
        $sinDepartamentos = $this->pais('País Sin Departamentos de Prueba', 9991);
        $formulario = $this->formulario()->set('currentStep', 6)
            ->set('modalidad_ejecucion', 'Presencial')
            ->set('territorio_ejecucion', 'Internacional')
            ->assertSee('Elija el país para ver sus departamentos y municipios.')
            ->set('pais_id', $sinDepartamentos->id)
            ->assertSet('pais', 'País Sin Departamentos de Prueba')
            ->assertSee('aún no tiene departamentos en el catálogo');
        $formulario->call('nextStep')
            ->assertHasErrors(['departamento_provincia', 'municipio_texto'])
            ->assertHasNoErrors(['pais_id', 'departamento_id', 'municipio_id']);

        // País con departamentos en el catálogo: se eligen como en Honduras.
        $conDepartamentos = $this->pais('País Con Departamentos de Prueba', 9992);
        $departamento = \App\Models\Demografia\Departamento::create(['pais_id' => $conDepartamentos->id, 'nombre' => 'Provincia de Prueba', 'codigo_departamento' => 9901]);
        $municipio = \App\Models\Demografia\Municipio::create(['departamento_id' => $departamento->id, 'nombre' => 'Municipio de Prueba', 'codigo_municipio' => 990101]);

        $formulario->set('pais_id', $conDepartamentos->id)->call('nextStep')
            ->assertHasErrors(['departamento_id', 'municipio_id'])
            ->assertHasNoErrors(['departamento_provincia', 'municipio_texto']);

        $formulario->set('departamento_id', $departamento->id)->set('municipio_id', $municipio->id);
        $componente = $formulario->instance();
        $datos = (new \ReflectionMethod($componente, 'payloadParcial'))->invoke($componente);
        $this->assertSame('País Con Departamentos de Prueba', $datos['pais']);
        $this->assertSame('Provincia de Prueba', $datos['departamento_provincia']);
        $this->assertSame('Municipio de Prueba', $datos['municipio']);
        $this->assertNull($datos['departamento']);
    }

    public function test_la_sede_principal_del_teletrabajo_sale_del_catalogo_y_se_recupera_al_editar(): void
    {
        $sinDepartamentos = $this->pais('País Sede Sin Catálogo de Prueba', 9994);
        $pais = $this->pais('País Sede de Prueba', 9995);
        $departamento = \App\Models\Demografia\Departamento::create(['pais_id' => $pais->id, 'nombre' => 'Provincia Sede de Prueba', 'codigo_departamento' => 9903]);
        $municipio = \App\Models\Demografia\Municipio::create(['departamento_id' => $departamento->id, 'nombre' => 'Cantón Sede de Prueba', 'codigo_municipio' => 990301]);
        $usuario = User::factory()->create();

        $formulario = Livewire::actingAs($usuario)->test(CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('currentStep', 6)
            ->set('territorio_ejecucion', 'Internacional')
            ->set('modalidad_ejecucion', '100% virtual');

        // País sin departamentos en el catálogo: se escriben.
        $formulario->set('pais_sede_id', $sinDepartamentos->id)
            ->assertSet('pais_sede_principal', 'País Sede Sin Catálogo de Prueba')
            ->call('nextStep')
            ->assertHasErrors(['departamento_provincia_sede_principal', 'municipio_sede_principal'])
            ->assertHasNoErrors(['pais_sede_id', 'departamento_sede_id', 'municipio_sede_id']);

        // País con catálogo: se eligen y su nombre queda en los campos que usan el PDF y el resumen.
        $formulario->set('pais_sede_id', $pais->id)
            ->assertViewHas('departamentosSede', fn ($opciones) => $opciones->all() === [$departamento->id => 'Provincia Sede de Prueba'])
            ->set('departamento_sede_id', $departamento->id)
            ->assertViewHas('municipiosSede', fn ($opciones) => $opciones->all() === [$municipio->id => 'Cantón Sede de Prueba'])
            ->set('municipio_sede_id', $municipio->id)
            ->set('aldea_ciudad_sede_principal', 'Ciudad Sede')
            ->assertSet('departamento_provincia_sede_principal', 'Provincia Sede de Prueba')
            ->assertSet('municipio_sede_principal', 'Cantón Sede de Prueba')
            ->call('guardarBorrador');

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $this->assertSame('País Sede de Prueba', $registro->pais_sede_principal);
        $this->assertSame('Provincia Sede de Prueba', $registro->departamento_provincia_sede_principal);
        $this->assertSame('Cantón Sede de Prueba', $registro->municipio_sede_principal);

        Livewire::actingAs($usuario)->test(EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('pais_sede_id', $pais->id)
            ->assertSet('departamento_sede_id', $departamento->id)
            ->assertSet('municipio_sede_id', $municipio->id);
    }

    public function test_un_borrador_internacional_recupera_pais_departamento_y_municipio(): void
    {
        $pais = $this->pais('País Recuperado de Prueba', 9993);
        $departamento = \App\Models\Demografia\Departamento::create(['pais_id' => $pais->id, 'nombre' => 'Provincia Recuperada', 'codigo_departamento' => 9902]);
        $municipio = \App\Models\Demografia\Municipio::create(['departamento_id' => $departamento->id, 'nombre' => 'Municipio Recuperado', 'codigo_municipio' => 990201]);
        $usuario = User::factory()->create();

        Livewire::actingAs($usuario)->test(CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('modalidad_ejecucion', 'Presencial')
            ->set('territorio_ejecucion', 'Internacional')
            ->set('pais_id', $pais->id)
            ->set('departamento_id', $departamento->id)
            ->set('municipio_id', $municipio->id)
            ->call('guardarBorrador');

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $this->assertSame('País Recuperado de Prueba', $registro->pais);
        $this->assertSame('Provincia Recuperada', $registro->departamento_provincia);

        Livewire::actingAs($usuario)->test(EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('pais_id', $pais->id)
            ->assertSet('departamento_id', $departamento->id)
            ->assertSet('municipio_id', $municipio->id);
    }

    public function test_al_guardar_se_limpia_el_bloque_que_no_aplica_y_la_region_sale_del_territorio(): void
    {
        $componente = new CreatePpsServicioSocial;
        $componente->territorio_ejecucion = 'Internacional';
        $componente->pais = 'Guatemala';
        $componente->caserio = 'Caserío previo';
        $componente->horas_presenciales = '200';
        $componente->pais_sede_principal = 'Guatemala';
        $componente->horas_teletrabajo = '100';
        $payload = fn () => (new \ReflectionMethod($componente, 'payloadParcial'))->invoke($componente);

        $componente->modalidad_ejecucion = 'Presencial';
        $datos = $payload();
        $this->assertSame('Extranjero', $datos['region']);
        $this->assertSame('Guatemala', $datos['pais']);
        $this->assertSame(200, $datos['horas_presenciales']);
        $this->assertNull($datos['pais_sede_principal']);
        $this->assertNull($datos['horas_teletrabajo']);

        $componente->modalidad_ejecucion = '100% virtual';
        $datos = $payload();
        $this->assertNull($datos['region']);
        $this->assertNull($datos['caserio']);
        $this->assertNull($datos['horas_presenciales']);
        $this->assertSame('Guatemala', $datos['pais_sede_principal']);
        $this->assertSame(100, $datos['horas_teletrabajo']);
    }

    public function test_la_institucion_se_elige_del_catalogo_y_sus_datos_no_se_editan(): void
    {
        $institucion = $this->institucion();
        $formulario = $this->formulario()->set('currentStep', 3);

        $formulario->call('usarInstitucionSeleccionada')->assertHasErrors('institucionBuscadaId');

        $formulario->set('institucionBuscadaId', (string) $institucion->id)
            ->call('usarInstitucionSeleccionada')
            ->assertSet('modoInstitucion', 'existente')
            ->assertSet('pps_institucion_id', $institucion->id)
            ->assertSet('institucion_representante', 'Ana López')
            ->assertSee('Hospital Escuela de Prueba')
            ->assertSee('Cambiar institución');

        // Con la institución se pide a quién va dirigida la solicitud y la modalidad.
        $formulario->call('nextStep')
            ->assertHasErrors(['destinatario_tratamiento', 'destinatario_nombre', 'destinatario_cargo', 'modalidad_ejecucion'])
            ->assertHasNoErrors(['pps_institucion_id', 'institucion_compromisos', 'jefe_directo_nombre']);

        // Los datos de la práctica (compromisos, instrumento y jefe directo) van después de la solicitud.
        $formulario->set('currentStep', 7)->call('nextStep')
            ->assertHasErrors(['institucion_compromisos', 'tipo_instrumento', 'jefe_directo_nombre', 'jefe_directo_correo', 'jefe_directo_grado']);
    }

    public function test_crear_una_institucion_exige_todos_sus_datos_y_evita_duplicados(): void
    {
        $this->institucion('Alcaldía Municipal de Prueba');
        $formulario = $this->formulario()->set('currentStep', 3)->call('crearInstitucionNueva')
            ->assertSet('modoInstitucion', 'nueva');

        $formulario->call('guardarInstitucionNueva')
            ->assertHasErrors(['institucion_nombre', 'institucion_nacionalidad', 'institucion_tipo', 'institucion_sector', 'institucion_direccion', 'institucion_representante', 'institucion_telefono', 'institucion_correo_rrhh']);

        $formulario->set('institucion_nombre', 'alcaldía municipal de prueba')
            ->set('institucion_nacionalidad', 'Internacional')
            ->set('institucion_tipo', 'gobierno_municipal')
            ->set('institucion_sector', 'servicios_funcion_publicos')
            ->set('institucion_direccion', 'Centro')
            ->set('institucion_representante', 'Luis Pérez')
            ->set('institucion_telefono', '2222-1111')
            ->set('institucion_correo_rrhh', 'rrhh@alcaldia.test')
            ->call('guardarInstitucionNueva')
            ->assertHasErrors(['institucion_pais']);

        $formulario->set('institucion_pais', 'Guatemala')->call('guardarInstitucionNueva');
        $this->assertStringContainsString('Usar seleccionada', $formulario->errors()->first('institucion_nombre'));
        $this->assertSame(1, PpsInstitucion::where('nombre', 'Alcaldía Municipal de Prueba')->count());

        $formulario->set('institucion_nombre', 'Cooperativa Nueva de Prueba')->call('guardarInstitucionNueva')
            ->assertHasNoErrors()
            ->assertSet('modoInstitucion', 'existente');
        $this->assertSame('Guatemala', PpsInstitucion::where('nombre', 'Cooperativa Nueva de Prueba')->value('pais'));
    }

    public function test_el_supervisor_se_elige_entre_los_docentes_y_trae_sus_datos(): void
    {
        $usuario = User::factory()->create(['email' => 'supervisor.prueba@unah.edu.hn']);
        $docente = Empleado::create([
            'nombre_completo' => 'Docente Supervisor de Prueba', 'numero_empleado' => '99887766',
            'celular' => '', 'sexo' => 'Femenino', 'user_id' => $usuario->id, 'tipo_empleado' => 'docente',
        ]);

        $formulario = $this->formulario()->set('currentStep', 8);
        $formulario->call('nextStep')->assertHasErrors('docente_supervisor_id');

        $formulario->set('docenteBusqueda', '99887766')->assertSee('Docente Supervisor de Prueba')
            ->call('seleccionarDocente', $docente->id)
            ->assertSet('docente_supervisor_nombre', 'Docente Supervisor de Prueba')
            ->assertSet('docente_correo', 'supervisor.prueba@unah.edu.hn');

        // Lo que el expediente trae queda de solo lectura; lo que le falta (celular) se escribe.
        $campos = $formulario->get('docenteCamposDelSistema');
        $this->assertContains('docente_numero_empleado', $campos);
        $this->assertNotContains('docente_celular', $campos);
        $formulario->call('nextStep')->assertHasErrors(['docente_celular', 'docente_jornada', 'docente_cubiculo']);
    }

    public function test_los_documentos_se_cargan_con_el_instrumento_y_el_convenio_solo_si_es_convenio_marco(): void
    {
        Storage::fake('public');
        $formulario = $this->formulario()->set('currentStep', 7)->set('tipo_instrumento', 'carta_intenciones')
            ->assertSee('Documentos adjuntos')
            ->assertSee('Carta de formalización de la PPS firmada por la contraparte');

        $formulario->call('nextStep')
            ->assertHasErrors('carta_formalizacion_archivo')
            ->assertHasNoErrors('convenio_marco_archivo');

        $formulario->set('carta_formalizacion_archivo', UploadedFile::fake()->create('carta.pdf', 10, 'application/pdf'))
            ->call('nextStep')
            ->assertHasNoErrors(['carta_formalizacion_archivo', 'convenio_marco_archivo']);

        $formulario->set('tipo_instrumento', 'convenio_marco')->call('nextStep')
            ->assertHasErrors('convenio_marco_archivo');

        $formulario->set('convenio_marco_archivo', UploadedFile::fake()->create('convenio.pdf', 10, 'application/pdf'))
            ->call('nextStep')
            ->assertHasNoErrors('convenio_marco_archivo');
    }

    public function test_el_documento_se_guarda_al_cargarlo_y_sigue_al_volver_a_abrir_el_borrador(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();
        $usuario->assignRole('docente');

        // En «crear» el primer documento ya crea el borrador con el archivo.
        Livewire::actingAs($usuario)->test(CreatePpsServicioSocial::class)
            ->set('currentStep', 7)
            ->set('carta_formalizacion_archivo', UploadedFile::fake()->create('Carta Formal Firmada.pdf', 10, 'application/pdf'))
            ->assertHasNoErrors()
            ->assertSet('carta_formalizacion_archivo', null);

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $ruta = $registro->archivo_carta_formalizacion;
        $this->assertNotNull($ruta);
        $this->assertStringEndsWith('/carta-formal-firmada.pdf', $ruta);
        $this->assertTrue($registro->adjunta_carta_formalizacion);
        Storage::disk('public')->assertExists($ruta);

        // Al volver a abrir el borrador (recargar o salir y entrar), el documento sigue cargado.
        $enlace = route('pps-servicio-social.anexo', ['id' => $registro->id, 'tipo' => 'carta-formalizacion']);
        $edicion = Livewire::actingAs($usuario)->test(EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('archivo_carta_formalizacion_actual', $ruta)
            ->set('currentStep', 7)
            ->assertSee('carta-formal-firmada.pdf')
            ->assertSeeHtml('href="'.$enlace.'"')
            ->assertDontSeeHtml('/storage/'.$ruta);

        // El enlace abre el archivo por la aplicación (con permisos), no por la URL pública de storage.
        $this->actingAs($usuario)->get($enlace)->assertOk();
        $otroDocente = User::factory()->create();
        $otroDocente->assignRole('docente');
        $this->actingAs($otroDocente)->get($enlace)->assertForbidden();

        // En la edición también se guarda al momento, y quitar el convenio marco igual.
        $edicion->set('convenio_marco_archivo', UploadedFile::fake()->create('convenio.pdf', 10, 'application/pdf'));
        $this->assertNotNull($registro->fresh()->archivo_convenio_marco);

        $edicion->call('quitarConvenioMarco');
        $this->assertNull($registro->fresh()->archivo_convenio_marco);
        $this->assertFalse($registro->fresh()->adjunta_convenio_marco);
        $this->assertSame($ruta, $registro->fresh()->archivo_carta_formalizacion);
    }

    public function test_el_ultimo_paso_resume_el_registro_y_lleva_a_editar(): void
    {
        $formulario = $this->formulario()
            ->set('numero_cuenta', '20201000123')
            ->set('estudiante_nombre_completo', 'María Fernanda López')
            ->set('currentStep', 9)
            ->assertSee('Paso 9: Revisión y envío')
            ->assertSee('María Fernanda López')
            ->assertSee('Hay pasos incompletos')
            ->assertSee('Enviar a firmar');

        $this->assertFalse($formulario->instance()->isStepComplete(9));

        // «Editar» regresa al paso del bloque.
        $formulario->call('goToStep', 2)->assertSet('currentStep', 2);

        // Enviar revisa todo el formulario y se detiene en el primer paso incompleto.
        $formulario->set('currentStep', 9)->call('abrirModalEnviar')->assertSet('currentStep', 1);
    }

    public function test_un_borrador_anterior_al_catalogo_deja_su_institucion_lista_para_crearla(): void
    {
        $usuario = User::factory()->create();
        Livewire::actingAs($usuario)->test(CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->set('institucion_nombre', 'Empresa Registrada Antes')
            ->set('institucion_representante', 'Carlos Díaz')
            ->call('guardarBorrador')
            ->assertHasNoErrors();

        $registro = PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail();
        $this->assertNull($registro->pps_institucion_id);

        Livewire::actingAs($usuario)->test(EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('modoInstitucion', 'nueva')
            ->assertSet('institucion_nombre', 'Empresa Registrada Antes')
            ->assertSet('institucion_representante', 'Carlos Díaz');

        $institucion = $this->institucion('Empresa del Catálogo');
        $registro->update(['pps_institucion_id' => $institucion->id]);

        Livewire::actingAs($usuario)->test(EditPpsServicioSocial::class, ['id' => $registro->id])
            ->assertSet('modoInstitucion', 'existente')
            ->assertSet('institucion_nombre', 'Empresa del Catálogo');
    }
}
