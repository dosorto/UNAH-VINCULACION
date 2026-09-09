<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreatePasantia;
use App\Models\Pasantia;
use App\Models\User;
use App\Services\Pasantias\PasantiaWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
            ->set('form.nombre_estudiante', 'Estudiante de prueba')
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
}
