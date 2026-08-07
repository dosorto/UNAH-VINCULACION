<?php

namespace App\Services\Workflow;

use App\Models\User;
use Illuminate\Support\Collection;

final class WorkflowResumptionPolicy
{
    /**
     * @param  Collection<int, array{stage_id: int|string, order: int, status: string, source: mixed}>  $snapshots
     */
    public function plan(Collection $snapshots): WorkflowResumptionPlan
    {
        if ($snapshots->isEmpty()) {
            throw new \RuntimeException('El último ciclo no contiene etapas que puedan reanudarse.');
        }

        $ordered = $snapshots
            ->map(function (array $snapshot): array {
                foreach (['stage_id', 'order', 'status', 'source'] as $requiredKey) {
                    if (! array_key_exists($requiredKey, $snapshot)) {
                        throw new \InvalidArgumentException('La etapa histórica no contiene todos los datos requeridos.');
                    }
                }

                $snapshot['order'] = (int) $snapshot['order'];
                $snapshot['status'] = strtoupper((string) $snapshot['status']);

                if ($snapshot['order'] < 1 || ! in_array($snapshot['status'], ['APPROVED', 'REJECTED', 'PENDING'], true)) {
                    throw new \RuntimeException('La etapa histórica contiene un orden o estado no reconocido.');
                }

                return $snapshot;
            })
            ->sortBy([
                ['order', 'asc'],
                ['stage_id', 'asc'],
            ])
            ->values();

        if ($ordered->pluck('stage_id')->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('El último ciclo contiene más de una revisión activa para la misma etapa.');
        }

        if ($ordered->pluck('order')->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('El último ciclo contiene más de una etapa con el mismo orden.');
        }

        $rejectedStages = $ordered->where('status', 'REJECTED')->values();

        if ($rejectedStages->count() !== 1) {
            throw new \RuntimeException('El último ciclo debe contener exactamente una etapa en subsanación.');
        }

        $rejectedStage = $rejectedStages->first();

        foreach ($ordered as $stage) {
            if ($stage['order'] < $rejectedStage['order'] && $stage['status'] !== 'APPROVED') {
                throw new \RuntimeException('El ciclo contiene estados inconsistentes antes de la etapa en subsanación.');
            }

            if ($stage['order'] > $rejectedStage['order'] && $stage['status'] !== 'PENDING') {
                throw new \RuntimeException('El ciclo contiene estados inconsistentes después de la etapa en subsanación.');
            }
        }

        return new WorkflowResumptionPlan(
            $rejectedStage,
            $ordered->filter(fn (array $stage): bool => $stage['order'] >= $rejectedStage['order'])->values(),
        );
    }

    public function eligibleReviewer(?User $user, ?string $requiredRole = null, bool $requiresEmployee = false): ?User
    {
        if (! $user || ! $user->exists || $user->trashed()) {
            return null;
        }

        if ($requiredRole && ! $user->hasRole($requiredRole)) {
            return null;
        }

        if ($requiresEmployee) {
            $user->loadMissing('empleado');

            if (! $user->empleado || $user->empleado->trashed()) {
                return null;
            }
        }

        return $user;
    }

    public function eligibleRecipient(?User $user, ?string $requiredRole = null, bool $requiresEmployee = false): ?User
    {
        $eligible = $this->eligibleReviewer($user, $requiredRole, $requiresEmployee);

        if (! $eligible || blank($eligible->email) || ! filter_var($eligible->email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $eligible;
    }
}
