<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\FichasActualizacionDocente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class FichaActualizacionRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ficha_pendiente_bloquea_solo_hasta_finalizacion_o_rechazo(): void
    {
        [, , $proyecto] = $this->contexto();
        $pendiente = FichaActualizacion::create($this->datosFicha($proyecto));

        $this->assertTrue(FichaActualizacion::pendientes()->whereKey($pendiente)->exists());

        $this->estado($pendiente, 'Actualizacion realizada');
        $this->assertFalse(FichaActualizacion::pendientes()->whereKey($pendiente)->exists());

        $rechazada = FichaActualizacion::create($this->datosFicha($proyecto));
        $this->estado($rechazada, 'Rechazado');
        $this->assertFalse(FichaActualizacion::pendientes()->whereKey($rechazada)->exists());
    }

    public function test_otro_coordinador_no_puede_abrir_una_ficha_ajena(): void
    {
        [, , $proyecto] = $this->contexto();
        $ficha = FichaActualizacion::create($this->datosFicha($proyecto));
        [$otroUsuario] = $this->contexto();

        Livewire::actingAs($otroUsuario)
            ->test(FichasActualizacionDocente::class)
            ->call('openView', $ficha->id)
            ->assertStatus(403);
    }

    public function test_la_ficha_se_muestra_sin_departamento_academico(): void
    {
        [$usuario, , $proyecto] = $this->contexto();
        $ficha = FichaActualizacion::create($this->datosFicha($proyecto));

        Livewire::actingAs($usuario)
            ->test(FichasActualizacionDocente::class)
            ->call('openView', $ficha->id)
            ->assertSee('No registrado');
    }

    private function contexto(): array
    {
        $usuario = User::factory()->create();
        $empleado = Empleado::create([
            'user_id' => $usuario->id,
            'nombre_completo' => 'Coordinador de prueba',
            'numero_empleado' => 'FICHA-'.uniqid(),
        ]);
        $proyecto = Proyecto::forceCreate(['nombre_proyecto' => 'Proyecto de prueba ficha']);
        EmpleadoProyecto::create([
            'proyecto_id' => $proyecto->id,
            'empleado_id' => $empleado->id,
            'rol' => 'Coordinador',
        ]);

        return [$usuario, $empleado, $proyecto];
    }

    private function datosFicha(Proyecto $proyecto): array
    {
        return [
            'proyecto_id' => $proyecto->id,
            'empleado_id' => $proyecto->coordinador_proyecto()->firstOrFail()->empleado_id,
            'fecha_registro' => now(),
        ];
    }

    private function estado(FichaActualizacion $ficha, string $nombre): void
    {
        $tipo = TipoEstado::firstOrCreate(['nombre' => $nombre]);
        $ficha->estado_proyecto()->create([
            'tipo_estado_id' => $tipo->id,
            'empleado_id' => $ficha->empleado_id,
            'fecha' => now(),
            'es_actual' => true,
        ]);
    }
}
