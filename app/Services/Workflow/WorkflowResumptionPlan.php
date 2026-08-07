<?php

namespace App\Services\Workflow;

use Illuminate\Support\Collection;

final readonly class WorkflowResumptionPlan
{
    /**
     * @param  array{stage_id: int|string, order: int, status: string, source: mixed}  $rejectedStage
     * @param  Collection<int, array{stage_id: int|string, order: int, status: string, source: mixed}>  $stages
     */
    public function __construct(
        public array $rejectedStage,
        public Collection $stages,
    ) {}
}
