<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreatePasantia;
use App\Livewire\Proyectos\Vinculacion\EditPasantia;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\User;
use App\Services\Integraciones\IntegracionApiService;
use App\Services\Pasantias\PasantiaWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PasantiaLivewireTest extends TestCase
{
    use DatabaseTransactions;

    public function test_siguiente_exige_campos_obligatorios_en_cada_seccion(): void
    {
        foreach ([1 => 'numero_cuenta', 2 => 'tipo_pasantia', 3 => 'descripcion_cargo',
            4 => 'nombre_institucion', 5 => 'nombre_contacto_directo', 6 => 'numero_empleado_docente'] as $paso => $campo) {
            Livewire::test(CreatePasantia::class)
                ->set('pasoActual', $paso)
                ->call('siguiente')
                ->assertSet('pasoActual', $paso)
                ->assertHasErrors(['form.'.$campo => 'required']);
        }
    }

    public function test_siguiente_exige_creditos_y_remuneracion_solo_si_aplican(): void
    {
        foreach ([2 => ['otorga_creditos', 'cantidad_creditos'], 3 => ['pasantia_remunerada', 'monto_remuneracion']] as $paso => [$respuesta, $cantidad]) {
            $componente = Livewire::test(CreatePasantia::class)
                ->set('autoguardadoActivo', false)
                ->set('pasoActual', $paso)
                ->set('form.'.$respuesta, 'Sí')
                ->call('siguiente')
                ->assertHasErrors(['form.'.$cantidad => 'required']);

            $componente->set('form.'.$respuesta, 'No')
                ->call('siguiente')
                ->assertHasNoErrors('form.'.$cantidad);
        }
    }

    public function test_muestra_catalogos_y_opciones_del_documento_oficial(): void
    {
        Livewire::test(CreatePasantia::class)
            ->assertSeeHtml('aria-label="Buscar: Escuela / departamento académico"')
            ->set('pasoActual', 4)
            ->assertSeeHtml('aria-label="Buscar: País"')
            ->assertSee('Gobierno Municipal')
            ->assertSee('Agricultura, alimentación y silvicultura');
    }

    public function test_selecciona_varias_asignaturas_sin_duplicados_y_recarga_el_borrador(): void
    {
        $primera = \App\Models\Asignatura::create(['codigo' => 'TEST-013-A', 'nombre' => 'Asignatura de prueba A', 'activa' => true]);
        $segunda = \App\Models\Asignatura::create(['codigo' => 'TEST-013-B', 'nombre' => 'Asignatura de prueba B', 'activa' => true]);
        $componente = Livewire::test(CreatePasantia::class)
            ->set('pasoActual', 3)
            ->call('agregarAsignatura', $primera->id)
            ->call('agregarAsignatura', $segunda->id)
            ->call('agregarAsignatura', $primera->id);
        $id = $componente->get('registroId');
        $this->assertCount(2, Pasantia::findOrFail($id)->asignaturas);
        Livewire::test(EditPasantia::class, ['id' => $id])
            ->assertSet('form.asignaturas.1.codigo', 'TEST-013-B')
            ->call('quitarAsignatura', 0)
            ->assertSet('form.asignaturas.0.codigo', 'TEST-013-B');
        $this->assertCount(1, Pasantia::findOrFail($id)->asignaturas);
    }

    public function test_puede_crear_borrador_vacio_y_conservar_nulos(): void
    {
        Livewire::test(CreatePasantia::class)
            ->call('guardarBorrador')
            ->assertSet('pasoActual', 1)
            ->assertSet('registroId', fn ($id) => is_int($id));

        $registro = Pasantia::latest('id')->firstOrFail();
        $this->assertSame('borrador', $registro->estado);
        $this->assertNull($registro->nombre_estudiante);
        $this->assertSame(Pasantia::PROCESO_FLUJO, $registro->proceso);
    }

    public function test_persiste_y_recarga_los_campos_del_estudiante_y_horas_sin_cruzarlos(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('autoguardadoActivo', false)
            ->set('form.numero_cuenta', '20212320423')
            ->set('form.nombre_estudiante', 'Juan Pérez')
            ->set('form.celular_estudiante', '99998888')
            ->set('form.correo_institucional', 'juan.perez@unah.hn')
            ->set('form.correo_personal', 'juanp@gmail.com')
            ->set('form.total_horas', 800)
            ->set('form.horas_semanales', 20)
            ->call('guardarBorrador', false);

        $pasantia = Pasantia::findOrFail($componente->get('registroId'));

        $this->assertSame('Juan Pérez', $pasantia->nombre_estudiante);
        $this->assertSame('20212320423', $pasantia->numero_cuenta);
        $this->assertSame('99998888', $pasantia->celular_estudiante);
        $this->assertSame('juan.perez@unah.hn', $pasantia->correo_institucional);
        $this->assertSame('juanp@gmail.com', $pasantia->correo_personal);
        $this->assertSame(800, $pasantia->total_horas);
        $this->assertSame(20, $pasantia->horas_semanales);

        $recargada = Pasantia::query()->findOrFail($pasantia->id);
        $this->assertSame('Juan Pérez', $recargada->nombre_estudiante);
        $this->assertSame('20212320423', $recargada->numero_cuenta);
        $this->assertSame('99998888', $recargada->celular_estudiante);
        $this->assertSame('juan.perez@unah.hn', $recargada->correo_institucional);
        $this->assertSame('juanp@gmail.com', $recargada->correo_personal);
        $this->assertSame(800, $recargada->total_horas);
        $this->assertSame(20, $recargada->horas_semanales);
    }

    public function test_el_envio_no_considera_vacios_los_booleanos_en_no(): void
    {
        $registro = new Pasantia([
            'fecha_registro' => '2026-09-10',
            'facultad_centro' => 'Centro Único',
            'escuela_departamento' => 'Departamento de Sistemas',
            'carrera' => 'Carrera Única',
            'numero_cuenta' => '20212320423',
            'nombre_estudiante' => 'Juan Pérez',
            'celular_estudiante' => '99998888',
            'correo_institucional' => 'juan.perez@unah.hn',
            'tipo_pasantia' => 'Pasantía profesional',
            'fecha_inicio' => '2026-09-15',
            'fecha_finalizacion' => '2026-10-15',
            'duracion_semanas' => 4,
            'total_horas' => 800,
            'horas_semanales' => 20,
            'pasantia_obligatoria' => false,
            'otorga_creditos' => false,
            'modalidad_ejecucion' => '100% presencial',
            'descripcion_experiencia' => 'Experiencia',
            'descripcion_cargo' => 'Cargo',
            'resumen_responsabilidades' => 'Responsabilidades',
            'area_departamento' => 'Área',
            'area_conocimiento' => 'Conocimiento',
            'descripcion_conocimientos_teoricos' => 'Conocimientos',
            'habilidades_desarrollar' => 'Habilidades',
            'pasantia_remunerada' => false,
            'nombre_institucion' => 'Institución',
            'direccion_institucion' => 'Dirección',
            'ciudad_institucion' => 'Tegucigalpa',
            'pais_institucion' => 'Honduras',
            'representante_legal' => 'Representante',
            'telefono_representante' => '22223333',
            'correo_rrhh' => 'rrhh@institucion.hn',
            'tipo_institucion' => 'Privada',
            'sector_institucion' => 'Empresa privada',
            'compromisos_institucion' => 'Compromisos',
            'nombre_contacto_directo' => 'Contacto',
            'celular_contacto_directo' => '99990000',
            'correo_contacto_directo' => 'contacto@institucion.hn',
            'cargo_contacto_directo' => 'Enlace',
            'grado_academico_contacto_directo' => 'Licenciatura',
            'tipo_instrumento' => 'carta_formal_solicitud',
            'nombre_docente_supervisor' => 'Docente',
            'numero_empleado_docente' => '900001',
            'celular_docente' => '99991111',
            'correo_docente' => 'docente@unah.hn',
            'categoria_docente' => 'Titular',
            'departamento_docente' => 'Sistemas',
            'jornada_laboral_docente' => 'Diurna',
            'ubicacion_cubiculo_docente' => 'Cubículo 1',
        ]);

        $this->assertSame([], app(PasantiaWorkflowService::class)->camposFaltantesParaEnvio($registro));
    }

    public function test_busqueda_de_estudiante_asigna_cada_respuesta_a_su_campo(): void
    {
        $this->mock(IntegracionApiService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstudiantePorCuenta')
                ->once()
                ->with('20212320423')
                ->andReturn([
                    'ok' => true,
                    'datos' => [
                        'numero_cuenta' => '20212320423',
                        'nombre_completo' => 'Juan Pérez',
                        'celular' => '99998888',
                        'correo_institucional' => 'juan.perez@unah.hn',
                    ],
                ]);
        });

        Livewire::test(CreatePasantia::class)
            ->set('form.numero_cuenta', '20212320423')
            ->call('buscarEstudiante')
            ->assertSet('form.numero_cuenta', '20212320423')
            ->assertSet('form.nombre_estudiante', 'Juan Pérez')
            ->assertSet('form.celular_estudiante', '99998888')
            ->assertSet('form.correo_institucional', 'juan.perez@unah.hn')
            ->assertSet('form.correo_personal', null)
            ->assertSet('form.total_horas', null)
            ->assertSet('form.horas_semanales', null);
    }

    public function test_busqueda_no_hidrata_campos_si_la_respuesta_no_corresponde_a_la_cuenta(): void
    {
        $this->mock(IntegracionApiService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstudiantePorCuenta')
                ->once()
                ->with('20212320423')
                ->andReturn([
                    'ok' => true,
                    'datos' => [
                        'numero_cuenta' => '789546',
                        'nombre_completo' => '789546',
                        'celular' => 'rh@gmail.com',
                    ],
                ]);
        });

        Livewire::test(CreatePasantia::class)
            ->set('form.numero_cuenta', '20212320423')
            ->call('buscarEstudiante')
            ->assertHasErrors(['form.numero_cuenta'])
            ->assertSet('form.numero_cuenta', '20212320423')
            ->assertSet('form.nombre_estudiante', null)
            ->assertSet('form.celular_estudiante', null);
    }

    public function test_rechaza_valores_con_formato_de_otro_campo(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.nombre_estudiante', '789546')
            ->assertHasErrors(['form.nombre_estudiante'])
            ->set('form.celular_estudiante', 'rh@gmail.com')
            ->assertHasErrors(['form.celular_estudiante'])
            ->set('form.total_horas', 'juan@unah.hn')
            ->assertHasErrors(['form.total_horas'])
            ->set('form.numero_cuenta', 'Juan Pérez')
            ->assertHasErrors(['form.numero_cuenta']);

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertNull($registro->nombre_estudiante);
        $this->assertNull($registro->celular_estudiante);
        $this->assertNull($registro->total_horas);
        $this->assertNull($registro->numero_cuenta);
    }

    public function test_navega_por_pasos_y_autoguarda_secciones(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.facultad_centro', 'Centro de prueba')
            ->set('form.escuela_departamento', 'Departamento de prueba')
            ->set('form.carrera', 'Carrera de prueba')
            ->set('form.numero_cuenta', '20240001')
            ->set('form.nombre_estudiante', 'Estudiante de prueba')
            ->set('form.celular_estudiante', '99998888')
            ->set('form.correo_institucional', 'estudiante@unah.hn')
            ->set('form.tipo_pasantia', 'Pasantía profesional')
            ->set('form.fecha_inicio', '2026-09-01')
            ->set('form.fecha_finalizacion', '2026-09-15')
            ->set('form.duracion_semanas', 2)
            ->set('form.total_horas', 100)
            ->set('form.horas_semanales', 20)
            ->set('form.pasantia_obligatoria', 'No')
            ->set('form.otorga_creditos', 'No')
            ->set('form.modalidad_ejecucion', '100% presencial')
            ->set('form.descripcion_experiencia', 'Experiencia de prueba')
            ->set('form.descripcion_cargo', 'Cargo de prueba')
            ->set('form.resumen_responsabilidades', 'Responsabilidades de prueba')
            ->set('form.area_departamento', 'Área de prueba')
            ->set('form.area_conocimiento', 'Conocimiento de prueba')
            ->set('form.descripcion_conocimientos_teoricos', 'Conocimientos de prueba')
            ->set('form.habilidades_desarrollar', 'Habilidades de prueba')
            ->set('form.pasantia_remunerada', 'No')
            ->set('form.nombre_institucion', 'Institución de prueba')
            ->call('siguiente')
            ->assertSet('pasoActual', 2)
            ->call('irAPaso', 4)
            ->assertSet('pasoActual', 4)
            ->call('anterior')
            ->assertSet('pasoActual', 3);

        $id = $componente->get('registroId');
        $this->assertSame('Estudiante de prueba', Pasantia::findOrFail($id)->nombre_estudiante);
        $this->assertSame('Institución de prueba', Pasantia::findOrFail($id)->nombre_institucion);
    }

    public function test_pasantia_tiene_ocho_pasos_en_el_orden_funcional(): void
    {
        $this->assertCount(8, CreatePasantia::PASOS);
        $this->assertSame('Firmas', CreatePasantia::PASOS[7]);
        $this->assertSame('Adjuntos', CreatePasantia::PASOS[8]);

        Livewire::test(CreatePasantia::class)
            ->call('irAPaso', 8)
            ->assertSet('pasoActual', 1);
    }

    public function test_convierte_si_y_no_a_booleanos_al_autoguardar(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.pasantia_obligatoria', 'Sí')
            ->set('form.otorga_creditos', 'No');

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertTrue((bool) $registro->pasantia_obligatoria);
        $this->assertFalse((bool) $registro->otorga_creditos);
    }

    public function test_no_persiste_texto_invalido_en_campos_numericos_durante_autoguardado(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.duracion_semanas', 'asdasd')
            ->assertHasErrors(['form.duracion_semanas']);

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertNull($registro->duracion_semanas);
    }

    public function test_no_marca_como_invalidos_los_campos_numericos_y_fechas_vacios(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('autoguardadoActivo', false)
            ->call('guardarBorrador', false)
            ->assertHasNoErrors();

        $this->assertNotNull(Pasantia::find($componente->get('registroId')));
    }

    public function test_no_persiste_fecha_invalida_durante_autoguardado(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.fecha_inicio', 'fecha-invalida')
            ->assertHasErrors(['form.fecha_inicio']);

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertNull($registro->fecha_inicio);
    }

    public function test_carga_y_guarda_los_archivos_del_paso_ocho(): void
    {
        Storage::fake('public');

        $componente = Livewire::test(CreatePasantia::class)
            ->set('cartaFormalizacionArchivo', UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'))
            ->set('convenioMarcoArchivo', UploadedFile::fake()->create('convenio.pdf', 100, 'application/pdf'))
            ->call('guardarBorrador', false);

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertNotNull($registro->archivo_carta_formalizacion);
        $this->assertNotNull($registro->archivo_convenio_marco);
        Storage::disk('public')->assertExists($registro->archivo_carta_formalizacion);
        Storage::disk('public')->assertExists($registro->archivo_convenio_marco);
    }

    public function test_bloquea_salto_a_paso_posterior_y_muestra_errores_del_anterior(): void
    {
        Livewire::test(CreatePasantia::class)
            ->call('irAPaso', 8)
            ->assertSet('pasoActual', 1)
            ->assertHasErrors([
                'form.facultad_centro',
                'form.carrera',
                'form.numero_cuenta',
                'form.nombre_estudiante',
            ]);
    }

    public function test_puede_avanzar_desde_firmas_sin_reglas_de_validacion(): void
    {
        Livewire::test(CreatePasantia::class)
            ->set('pasoActual', 7)
            ->call('siguiente')
            ->assertSet('pasoActual', 8);
    }

    public function test_el_ultimo_paso_valida_completitud_antes_de_abrir_el_envio(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('pasoActual', 8)
            ->call('siguiente');

        $this->assertNotNull($componente->get('registroId'));
        $componente
            ->assertSet('showEnviarModal', false)
            ->assertHasErrors('flujo');
    }

    public function test_puede_regresar_sin_validar_bloqueantemente(): void
    {
        Livewire::test(CreatePasantia::class)
            ->set('pasoActual', 3)
            ->set('form.nombre_estudiante', '')
            ->call('anterior')
            ->assertSet('pasoActual', 2);
    }

    public function test_conserva_fecha_de_inicio_y_fecha_final_durante_el_autoguardado(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.fecha_inicio', '2026-09-01')
            ->assertSet('form.fecha_inicio', '2026-09-01')
            ->set('form.fecha_finalizacion', '2026-09-15')
            ->assertSet('form.fecha_finalizacion', '2026-09-15');

        $this->assertSame('2026-09-01', $componente->get('form.fecha_inicio'));
        $this->assertSame('2026-09-15', $componente->get('form.fecha_finalizacion'));

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertSame('2026-09-01', $registro->fecha_inicio->format('Y-m-d'));
        $this->assertSame('2026-09-15', $registro->fecha_finalizacion->format('Y-m-d'));
    }

    public function test_conserva_ambas_fechas_al_navegar_y_regresar_al_paso_dos(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('autoguardadoActivo', false)
            ->set('form.facultad_centro', 'Centro de prueba')
            ->set('form.escuela_departamento', 'Departamento de prueba')
            ->set('form.carrera', 'Carrera de prueba')
            ->set('form.numero_cuenta', '20240001')
            ->set('form.nombre_estudiante', 'Estudiante de prueba')
            ->set('form.celular_estudiante', '99998888')
            ->set('form.correo_institucional', 'estudiante@unah.hn')
            ->set('form.tipo_pasantia', 'Pasantía profesional')
            ->set('form.fecha_inicio', '2026-10-03')
            ->set('form.fecha_finalizacion', '2026-10-20')
            ->set('form.duracion_semanas', 3)
            ->set('form.total_horas', 100)
            ->set('form.horas_semanales', 20)
            ->set('form.pasantia_obligatoria', 'No')
            ->set('form.otorga_creditos', 'No')
            ->set('form.modalidad_ejecucion', '100% presencial')
            ->call('guardarBorrador', false)
            ->call('irAPaso', 3)
            ->call('irAPaso', 2);

        $componente
            ->assertSet('form.fecha_inicio', '2026-10-03')
            ->assertSet('form.fecha_finalizacion', '2026-10-20');
    }

    public function test_hidrata_las_fechas_de_un_borrador_existente_en_formato_html(): void
    {
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-FECHAS-'.uniqid(),
            'estado' => 'borrador',
            'fecha_inicio' => '2026-11-01',
            'fecha_finalizacion' => '2026-11-30',
        ]);

        Livewire::test(CreatePasantia::class, ['id' => $registro->id])
            ->assertSet('form.fecha_inicio', '2026-11-01')
            ->assertSet('form.fecha_finalizacion', '2026-11-30');
    }

    public function test_hidrata_la_fecha_de_registro_en_formato_html(): void
    {
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-FECHA-REGISTRO-'.uniqid(),
            'estado' => 'borrador',
            'fecha_registro' => '2026-11-05',
        ]);

        Livewire::test(CreatePasantia::class, ['id' => $registro->id])
            ->assertSet('form.fecha_registro', '2026-11-05');
    }

    public function test_hidrata_explicitamente_los_ocho_pasos_sin_reutilizar_posiciones(): void
    {
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-HIDRATACION-'.uniqid(),
            'estado' => 'borrador',
            'fecha_registro' => '2026-11-05',
            'facultad_centro' => 'UNAH CHOLUTECA',
            'escuela_departamento' => 'Escuela de Sistemas',
            'carrera' => 'Ingeniería en Sistemas',
            'numero_cuenta' => '20212326154',
            'nombre_estudiante' => 'María Laínez',
            'celular_estudiante' => '99998888',
            'correo_institucional' => 'maria.lainez@unah.hn',
            'correo_personal' => 'maria777@gmail.com',
            'tipo_pasantia' => 'Pasantía profesional',
            'fecha_inicio' => '2027-02-16',
            'fecha_finalizacion' => '2027-06-16',
            'duracion_semanas' => 17,
            'total_horas' => 800,
            'horas_semanales' => 20,
            'pasantia_obligatoria' => true,
            'otorga_creditos' => false,
            'cantidad_creditos' => 0,
            'modalidad_ejecucion' => '100% presencial',
            'descripcion_experiencia' => 'Experiencia registrada',
            'descripcion_cargo' => 'Desarrollo de sistemas',
            'resumen_responsabilidades' => 'Responsabilidades registradas',
            'area_departamento' => 'Desarrollo',
            'area_conocimiento' => 'Ingeniería de software',
            'codigo_asignatura' => 'IS-401',
            'nombre_asignatura' => 'Sistemas Operativos',
            'descripcion_conocimientos_teoricos' => 'Conocimientos registrados',
            'habilidades_desarrollar' => 'Habilidades registradas',
            'pasantia_remunerada' => false,
            'monto_remuneracion' => 0,
            'nombre_institucion' => 'Institución de prueba',
            'direccion_institucion' => 'Dirección de prueba',
            'ciudad_institucion' => 'Tegucigalpa',
            'pais_institucion' => 'Honduras',
            'representante_legal' => 'Representante Legal',
            'telefono_representante' => '22223333',
            'correo_rrhh' => 'rrhh@institucion.hn',
            'tipo_institucion' => 'Privada',
            'sector_institucion' => 'Empresa privada',
            'compromisos_institucion' => 'Compromisos registrados',
            'nombre_contacto_directo' => 'Contacto Directo',
            'celular_contacto_directo' => '99990000',
            'correo_contacto_directo' => 'contacto@institucion.hn',
            'cargo_contacto_directo' => 'Jefe directo',
            'grado_academico_contacto_directo' => 'Licenciatura',
            'tipo_instrumento' => 'carta_formal_solicitud',
            'nombre_docente_supervisor' => 'Docente Supervisor',
            'numero_empleado_docente' => '900001',
            'celular_docente' => '99991111',
            'correo_docente' => 'docente@unah.hn',
            'categoria_docente' => 'Titular',
            'departamento_docente' => 'Sistemas',
            'jornada_laboral_docente' => 'Diurna',
            'ubicacion_cubiculo_docente' => 'Cubículo 1',
            'nombre_firma_coordinador' => 'Coordinador',
            'firma_coordinador' => 'firmas/coordinador.png',
            'nombre_firma_supervisor' => 'Supervisor',
            'firma_supervisor' => 'firmas/supervisor.png',
            'nombre_firma_estudiante' => 'María Laínez',
            'firma_estudiante' => 'firmas/estudiante.png',
            'adjunta_carta_formalizacion' => true,
            'archivo_carta_formalizacion' => 'pasantias/carta.pdf',
            'adjunta_convenio_marco' => false,
            'archivo_convenio_marco' => null,
        ]);

        Livewire::test(EditPasantia::class, ['id' => $registro->id])
            ->assertSet('form.escuela_departamento', 'Escuela de Sistemas')
            ->assertSet('form.numero_cuenta', '20212326154')
            ->assertSet('form.nombre_estudiante', 'María Laínez')
            ->assertSet('form.celular_estudiante', '99998888')
            ->assertSet('form.correo_institucional', 'maria.lainez@unah.hn')
            ->assertSet('form.correo_personal', 'maria777@gmail.com')
            ->assertSet('form.fecha_inicio', '2027-02-16')
            ->assertSet('form.fecha_finalizacion', '2027-06-16')
            ->assertSet('form.total_horas', 800)
            ->assertSet('form.horas_semanales', 20)
            ->assertSet('form.codigo_asignatura', 'IS-401')
            ->assertSet('form.nombre_asignatura', 'Sistemas Operativos')
            ->assertSet('form.nombre_institucion', 'Institución de prueba')
            ->assertSet('form.nombre_contacto_directo', 'Contacto Directo')
            ->assertSet('form.nombre_docente_supervisor', 'Docente Supervisor')
            ->assertSet('form.archivo_carta_formalizacion', 'pasantias/carta.pdf')
            ->assertSet('form.archivo_convenio_marco', null);
    }

    public function test_limpia_del_borrador_los_valores_incompatibles_sin_redistribuirlos(): void
    {
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-CRUZADO-'.uniqid(),
            'estado' => 'borrador',
            'escuela_departamento' => '2027-02-16',
            'nombre_estudiante' => 'Código de asignatura',
            'celular_estudiante' => '12',
            'horas_semanales' => 45897823,
            'nombre_asignatura' => 'rrhh@unah.hn',
            'descripcion_experiencia' => 'Descripción de la experiencia y resultados',
            'area_conocimiento' => 'Representante legal',
            'nombre_institucion' => 'Nombre del contacto directo',
            'ciudad_institucion' => 'contacto@unah.hn',
            'representante_legal' => 'Representante legal',
        ]);

        Livewire::test(EditPasantia::class, ['id' => $registro->id])
            ->assertSet('form.escuela_departamento', null)
            ->assertSet('form.nombre_estudiante', null)
            ->assertSet('form.celular_estudiante', null)
            ->assertSet('form.horas_semanales', null)
            ->assertSet('form.nombre_asignatura', null)
            ->assertSet('form.descripcion_experiencia', null)
            ->assertSet('form.area_conocimiento', null)
            ->assertSet('form.nombre_institucion', null)
            ->assertSet('form.ciudad_institucion', null)
            ->assertSet('form.representante_legal', null)
            ->assertHasErrors([
                'form.escuela_departamento',
                'form.nombre_estudiante',
                'form.celular_estudiante',
                'form.horas_semanales',
                'form.nombre_asignatura',
                'form.descripcion_experiencia',
                'form.area_conocimiento',
                'form.nombre_institucion',
                'form.ciudad_institucion',
                'form.representante_legal',
            ]);

        $registro->refresh();
        $this->assertNull($registro->escuela_departamento);
        $this->assertNull($registro->nombre_estudiante);
        $this->assertNull($registro->celular_estudiante);
        $this->assertNull($registro->horas_semanales);
        $this->assertNull($registro->nombre_asignatura);
        $this->assertNull($registro->descripcion_experiencia);
        $this->assertNull($registro->area_conocimiento);
        $this->assertNull($registro->nombre_institucion);
        $this->assertNull($registro->ciudad_institucion);
        $this->assertNull($registro->representante_legal);
        $this->assertNull($registro->fecha_inicio);
        $this->assertNull($registro->codigo_asignatura);
        $this->assertNull($registro->cantidad_creditos);
    }

    public function test_editar_una_fecha_no_altera_la_otra(): void
    {
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-EDITAR-FECHA-'.uniqid(),
            'estado' => 'borrador',
            'fecha_inicio' => '2026-12-01',
            'fecha_finalizacion' => '2026-12-15',
        ]);

        Livewire::test(CreatePasantia::class, ['id' => $registro->id])
            ->set('form.fecha_inicio', '2026-12-05');

        $registro->refresh();
        $this->assertSame('2026-12-05', $registro->fecha_inicio->format('Y-m-d'));
        $this->assertSame('2026-12-15', $registro->fecha_finalizacion->format('Y-m-d'));
    }

    public function test_fechas_vacias_se_guardan_como_null(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('autoguardadoActivo', false)
            ->set('form.fecha_inicio', '')
            ->set('form.fecha_finalizacion', '')
            ->call('guardarBorrador', false);

        $registro = Pasantia::findOrFail($componente->get('registroId'));
        $this->assertNull($registro->fecha_inicio);
        $this->assertNull($registro->fecha_finalizacion);
    }

    public function test_rechaza_fecha_final_anterior_a_la_fecha_de_inicio(): void
    {
        Livewire::test(CreatePasantia::class)
            ->set('autoguardadoActivo', false)
            ->set('pasoActual', 2)
            ->set('form.fecha_inicio', '2027-01-20')
            ->set('form.fecha_finalizacion', '2027-01-10')
            ->call('siguiente')
            ->assertSet('pasoActual', 2)
            ->assertHasErrors(['form.fecha_finalizacion']);
    }

    public function test_recupera_un_registro_existente_en_modo_edicion(): void
    {
        $registro = Pasantia::create(['codigo_registro' => 'PAS-RECUPERAR-'.uniqid(), 'estado' => 'borrador', 'nombre_estudiante' => 'Registro existente']);

        Livewire::test(CreatePasantia::class, ['id' => $registro->id])
            ->assertSet('registroId', $registro->id)
            ->assertSet('modoEdicion', true)
            ->assertSet('form.nombre_estudiante', 'Registro existente');
    }

    public function test_subsanacion_preserva_archivo_observacion_y_etapa(): void
    {
        $usuario = User::factory()->create();
        $empleado = DB::table('empleado')->insertGetId(['nombre_completo' => 'Empleado de prueba', 'numero_empleado' => 'TEST-'.uniqid(), 'tipo_empleado' => 'docente', 'user_id' => $usuario->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($usuario);
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-SUBSANAR-'.uniqid(), 'estado' => 'rechazado', 'created_by' => $usuario->id,
            'motivo_rechazo' => 'Corregir datos de la institución', 'archivo_carta_formalizacion' => 'adjuntos/carta.pdf',
            'flujo_aprobacion_id' => DB::table('flujos_aprobacion')->where('codigo', 'PASANTIAS_FORM_DVUS_013')->value('id'),
            'etapa_actual_id' => 7,
        ]);
        $rechazado = DB::table('tipo_estado')->where('nombre', 'Rechazado')->value('id');
        $this->assertNotNull($rechazado);
        DB::table('estado_proyecto')->insert(['empleado_id' => $empleado, 'tipo_estado_id' => $rechazado, 'fecha' => now(), 'comentario' => 'Corregir datos de la institución', 'es_actual' => true, 'estadoable_id' => $registro->id, 'estadoable_type' => Pasantia::class, 'created_at' => now(), 'updated_at' => now()]);

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\ShowPasantia::class, ['id' => $registro->id])->call('iniciarSubsanacion');
        $registro->refresh();
        $this->assertSame('subsanacion', $registro->estado);
        $this->assertSame('adjuntos/carta.pdf', $registro->archivo_carta_formalizacion);
        $this->assertSame(7, $registro->etapa_actual_id);
        $this->assertSame('Corregir datos de la institución', $registro->motivo_rechazo);
    }

    public function test_rechazo_exige_comentario(): void
    {
        $registro = new Pasantia;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('obligatorio');
        app(PasantiaWorkflowService::class)->rechazar($registro, 1, '   ');
    }

    public function test_creador_puede_eliminar_su_borrador_y_se_registra_la_auditoria(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-ELIMINAR-'.uniqid(),
            'created_by' => $usuario->id,
            'estado' => 'borrador',
        ]);

        Livewire::test(\App\Livewire\Proyectos\Vinculacion\ShowPasantia::class, ['id' => $registro->id])
            ->call('eliminarBorrador');

        $this->assertSoftDeleted('pasantias', ['id' => $registro->id]);
        $this->assertNull(Pasantia::find($registro->id));
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Pasantia::class,
            'subject_id' => $registro->id,
            'description' => 'Borrador eliminado lógicamente',
        ]);
    }

    public function test_otro_usuario_no_puede_eliminar_borrador_de_pasantia(): void
    {
        $creador = User::factory()->create();
        $otro = User::factory()->create();
        $registro = Pasantia::create([
            'codigo_registro' => 'PAS-NO-ELIMINAR-'.uniqid(),
            'created_by' => $creador->id,
            'estado' => 'borrador',
        ]);

        $this->actingAs($otro);
        $this->get(route('pasantias.show', ['id' => $registro->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('pasantias', ['id' => $registro->id, 'deleted_at' => null]);
    }

    /** @dataProvider estados_no_eliminables */
    public function test_no_puede_eliminar_pasantia_fuera_de_borrador(string $estado): void
    {
        $usuario = User::factory()->make(['id' => 77]);
        $registro = new Pasantia(['created_by' => $usuario->id]);
        $estadoActual = new EstadoProyecto;
        $tipoEstado = new TipoEstado;
        $tipoEstado->nombre = $estado;
        $estadoActual->setRelation('tipoestado', $tipoEstado);
        $registro->setRelation('estadoActual', $estadoActual);

        $this->assertFalse($registro->puedeEliminarBorrador($usuario->id));
    }

    public static function estados_no_eliminables(): array
    {
        return [['en_revision'], ['aprobado'], ['rechazado'], ['subsanacion'], ['cancelado'], ['finalizado']];
    }
}
