<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkflowResumptionPolicyTest extends TestCase
{
    #[DataProvider('validCycles')]
    public function test_it_resumes_from_the_only_rejected_stage(array $statuses, array $expectedStages): void
    {
        $snapshots = collect($statuses)
            ->map(fn (string $status, int $index): array => [
                'stage_id' => $index + 10,
                'order' => $index + 1,
                'status' => $status,
                'source' => 'stage-'.($index + 1),
            ]);

        $plan = (new WorkflowResumptionPolicy)->plan($snapshots);

        self::assertSame($expectedStages, $plan->stages->pluck('source')->all());
        self::assertSame('REJECTED', $plan->rejectedStage['status']);
    }

    public static function validCycles(): array
    {
        return [
            'initial stage' => [['REJECTED', 'PENDING', 'PENDING'], ['stage-1', 'stage-2', 'stage-3']],
            'intermediate stage' => [['APPROVED', 'REJECTED', 'PENDING'], ['stage-2', 'stage-3']],
            'final stage' => [['APPROVED', 'APPROVED', 'REJECTED'], ['stage-3']],
        ];
    }

    #[DataProvider('invalidCycles')]
    public function test_it_rejects_ambiguous_or_inconsistent_cycles(Collection $snapshots): void
    {
        $this->expectException(\RuntimeException::class);

        (new WorkflowResumptionPolicy)->plan($snapshots);
    }

    public static function invalidCycles(): array
    {
        $stage = fn (int $id, int $order, string $status): array => [
            'stage_id' => $id,
            'order' => $order,
            'status' => $status,
            'source' => 'stage-'.$id,
        ];

        return [
            'without rejection' => [collect([$stage(1, 1, 'APPROVED'), $stage(2, 2, 'PENDING')])],
            'multiple rejections' => [collect([$stage(1, 1, 'REJECTED'), $stage(2, 2, 'REJECTED')])],
            'inconsistent previous stage' => [collect([$stage(1, 1, 'PENDING'), $stage(2, 2, 'REJECTED')])],
            'inconsistent later stage' => [collect([$stage(1, 1, 'REJECTED'), $stage(2, 2, 'APPROVED')])],
            'duplicate stage' => [collect([$stage(1, 1, 'REJECTED'), $stage(1, 2, 'PENDING')])],
            'duplicate order' => [collect([$stage(1, 1, 'REJECTED'), $stage(2, 1, 'PENDING')])],
            'unknown state' => [collect([$stage(1, 1, 'UNKNOWN')])],
        ];
    }

    public function test_it_requires_a_deliverable_email_for_a_historical_recipient(): void
    {
        $valid = (new User)->forceFill(['email' => 'reviewer@example.test']);
        $valid->exists = true;
        $invalid = (new User)->forceFill(['email' => 'not-an-email']);
        $invalid->exists = true;

        $policy = new WorkflowResumptionPolicy;

        self::assertSame($valid, $policy->eligibleRecipient($valid));
        self::assertNull($policy->eligibleRecipient($invalid));
    }
}
