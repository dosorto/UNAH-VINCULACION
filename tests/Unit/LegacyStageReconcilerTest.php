<?php

namespace Tests\Unit;

use App\Services\Proyecto\LegacyStageReconciler;
use App\Services\Workflow\WorkflowResumptionPolicy;
use PHPUnit\Framework\TestCase;

class LegacyStageReconcilerTest extends TestCase
{
    private function reconcile(array $signatures, array $stages = [10, 20, 30]): array
    {
        return (new LegacyStageReconciler)->reconcile(
            collect($stages)->map(fn ($id) => (object) ['id' => $id]),
            collect($signatures)->map(fn ($s) => (object) ['id' => $s[0], 'stage' => $s[1], 'estado_revision' => $s[2]]),
            fn ($signature, $stage) => $signature->stage === $stage->id
        );
    }

    public function test_no_omite_jefe_pendiente_antes_de_enlace(): void
    {
        $result = $this->reconcile([[1, 20, 'Pendiente'], [2, 10, 'Pendiente']]);
        $this->assertSame([10, 20, 30], $result['pendientes']->pluck('id')->all());
        $this->assertCount(0, $result['completadas']);
        $this->assertSame('Sin evidencia', $result['clasificacion'][30]['estado']);
    }

    public function test_preserva_aprobacion_aunque_el_nuevo_orden_la_coloca_despues(): void
    {
        $result = $this->reconcile([[1, 20, 'Aprobado'], [2, 10, 'Pendiente']]);
        $this->assertSame([10, 30], $result['pendientes']->pluck('id')->all());
        $this->assertSame([20], $result['completadas']->pluck('id')->all());
        $this->assertSame(1, $result['clasificacion'][20]['firma_legacy_id']);
    }

    public function test_no_reutiliza_una_aprobacion_superada_por_un_rechazo_o_nueva_revision(): void
    {
        $result = $this->reconcile([[1, 10, 'Aprobado'], [2, 10, 'Rechazado'], [3, 20, 'Aprobado'], [4, 20, 'Pendiente']]);
        $this->assertCount(0, $result['completadas']);
        $this->assertSame('Rechazado', $result['clasificacion'][10]['estado']);
        $this->assertSame('Pendiente', $result['clasificacion'][20]['estado']);
    }

    public function test_bloquea_equivalencia_ambigua_y_revision_pendiente_eliminada(): void
    {
        $result = $this->reconcile([[1, 10, 'Aprobado'], [2, 40, 'Pendiente']], [10, 10, 30]);
        $this->assertCount(2, $result['bloqueos']);
        $this->assertCount(0, $result['completadas']);
    }

    public function test_subsanacion_adoptada_reanuda_todas_las_pendientes_en_orden_sin_inventar_rechazos(): void
    {
        $stages = collect([
            ['stage_id' => 10, 'order' => 1, 'status' => 'PENDING', 'source' => 1],
            ['stage_id' => 20, 'order' => 2, 'status' => 'REJECTED', 'source' => 2],
            ['stage_id' => 30, 'order' => 3, 'status' => 'PENDING', 'source' => 3],
        ]);
        $policy = new WorkflowResumptionPolicy;
        $plan = $policy->plan($stages, true);
        $this->assertSame(20, $plan->rejectedStage['stage_id']);
        $this->assertSame([10, 20, 30], $plan->stages->pluck('stage_id')->all());
        $this->expectException(\RuntimeException::class);
        $policy->plan($stages); // Los ciclos ordinarios mantienen la validación estricta.
    }
}
