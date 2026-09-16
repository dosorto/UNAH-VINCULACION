<?php

namespace Tests\Feature;

use App\Models\Pasantia;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PasantiaModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pasantia_permite_borrador_incompleto_y_aplica_casts(): void
    {
        $pasantia = Pasantia::create([
            'codigo_registro' => 'PAS-TEST-'.uniqid(),
            'proceso' => Pasantia::PROCESO_FLUJO,
            'estado' => 'borrador',
            'asignaturas' => [['codigo' => 'A-101']],
            'pasantia_obligatoria' => true,
            'fecha_inicio' => '2026-09-01',
        ]);

        $this->assertSame('borrador', $pasantia->estado);
        $this->assertTrue($pasantia->pasantia_obligatoria);
        $this->assertSame('2026-09-01', $pasantia->fecha_inicio->format('Y-m-d'));
        $this->assertSame([['codigo' => 'A-101']], $pasantia->asignaturas);
        $this->assertNull($pasantia->nombre_estudiante);
    }

    public function test_pasantia_expone_relaciones_de_flujo_e_historial(): void
    {
        $pasantia = new Pasantia();

        $this->assertSame('pasantias', $pasantia->getTable());
        $this->assertSame('flujo_aprobacion_id', $pasantia->flujoAprobacion()->getForeignKeyName());
        $this->assertSame('etapa_actual_id', $pasantia->etapaActual()->getForeignKeyName());
        $this->assertSame('firmable_type', $pasantia->firmasDeEtapa()->getMorphType());
        $this->assertSame('estadoable_type', $pasantia->historialEstados()->getMorphType());
    }
}
