<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreatePasantia;
use App\Models\Pasantia;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\User;
use App\Services\Pasantias\PasantiaWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PasantiaLivewireTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_navega_por_pasos_y_autoguarda_secciones(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('form.facultad_centro', 'Centro de prueba')
            ->set('form.carrera', 'Carrera de prueba')
            ->set('form.numero_cuenta', '20240001')
            ->set('form.nombre_estudiante', 'Estudiante de prueba')
            ->set('form.tipo_pasantia', 'Pasantía profesional')
            ->set('form.fecha_inicio', '2026-09-01')
            ->set('form.fecha_finalizacion', '2026-09-15')
            ->set('form.total_horas', 100)
            ->set('form.modalidad_ejecucion', '100% presencial')
            ->set('form.descripcion_experiencia', 'Experiencia de prueba')
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

    public function test_finalizar_paso_ocho_redirige_al_detalle(): void
    {
        $componente = Livewire::test(CreatePasantia::class)
            ->set('pasoActual', 8)
            ->call('siguiente');

        $this->assertNotNull($componente->get('registroId'));
        $componente->assertRedirect(route('pasantias.show', ['id' => $componente->get('registroId')]));
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
            ->set('form.carrera', 'Carrera de prueba')
            ->set('form.numero_cuenta', '20240001')
            ->set('form.nombre_estudiante', 'Estudiante de prueba')
            ->set('form.tipo_pasantia', 'Pasantía profesional')
            ->set('form.fecha_inicio', '2026-10-03')
            ->set('form.fecha_finalizacion', '2026-10-20')
            ->set('form.total_horas', 100)
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

    public function test_editar_una_fecha_no altera_la_otra(): void
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
        $registro = new Pasantia();
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
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(\App\Livewire\Proyectos\Vinculacion\ShowPasantia::class, ['id' => $registro->id])
            ->call('eliminarBorrador');

        $this->assertDatabaseHas('pasantias', ['id' => $registro->id, 'deleted_at' => null]);
    }

    /** @dataProvider estados_no_eliminables */
    public function test_no_puede_eliminar_pasantia_fuera_de_borrador(string $estado): void
    {
        $usuario = User::factory()->make(['id' => 77]);
        $registro = new Pasantia(['created_by' => $usuario->id]);
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
}
