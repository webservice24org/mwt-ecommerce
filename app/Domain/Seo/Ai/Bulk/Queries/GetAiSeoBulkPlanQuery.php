<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Queries;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;

final readonly class GetAiSeoBulkPlanQuery
{
    /**
     * @return array<string, mixed>
     */
    public function handle(
        int $jobId,
    ): array {
        $job =
            AiSeoBulkJob::query()
                ->with(
                    'items',
                )
                ->findOrFail(
                    $jobId,
                );

        $execution =
            $job->execution_summary
            ?? $this->executionSummary(
                $job,
            );

        return [
            'id' => $job->id,

            'reference' => $job->reference,

            'status' => $job
                ->status
                ->value,

            'plan_hash' => $job
                ->plan_hash,

            'entity_types' => $job
                ->entity_types,

            'filters' => $job
                ->filters,

            'plan_summary' => $job
                ->summary,

            'execution_summary' => $execution,

            'planned_by_admin_id' => $job
                ->planned_by_admin_id,

            'planned_at' => $job
                ->planned_at
                ->toIso8601String(),

            'started_at' => $job
                ->started_at
                ?->toIso8601String(),

            'last_dispatched_at' => $job
                ->last_dispatched_at
                ?->toIso8601String(),

            'completed_at' => $job
                ->completed_at
                ?->toIso8601String(),

            'actions' => [
                'can_execute' => in_array(
                    $job->status,
                    [
                        AiSeoBulkJobStatus::Planned,
                        AiSeoBulkJobStatus::Running,
                    ],
                    true,
                ),

                'can_retry_failed' => (int) (
                    $execution[
                        'failed'
                    ]
                    ?? 0
                ) > 0,

                'can_apply' => AiSeoBulkJobItem::query()
                    ->where(
                        'ai_seo_bulk_job_id',
                        $job->id,
                    )
                    ->where(
                        'execution_status',
                        AiSeoBulkItemExecutionStatus::Completed
                            ->value,
                    )
                    ->whereNotNull(
                        'ai_seo_request_id',
                    )
                    ->exists(),
            ],

            'queue' => [
                'name' => (string) config(
                    'ai-seo-bulk.queue',
                    'ai-seo',
                ),

                'max_attempts' => max(
                    1,
                    (int) config(
                        'ai-seo-bulk.max_attempts',
                        3,
                    ),
                ),
            ],

            'items' => $job
                ->items
                ->map(
                    static fn (
                        AiSeoBulkJobItem $item,
                    ): array => [
                        'id' => $item->id,

                        'position' => $item
                            ->position,

                        'entity_type' => $item
                            ->entity_type
                            ->value,

                        'entity_id' => $item
                            ->entity_id,

                        'entity_name' => $item
                            ->entity_name,

                        'entity_slug' => $item
                            ->entity_slug,

                        'state' => $item
                            ->state
                            ->value,

                        'planned_action' => $item
                            ->planned_action
                            ->value,

                        'execution_status' => $item
                            ->execution_status
                            ->value,

                        'attempt_count' => $item
                            ->attempt_count,

                        'ai_seo_request_id' => $item
                            ->ai_seo_request_id,

                        'reason' => $item
                            ->reason,

                        'execution_error_code' => $item
                            ->execution_error_code,

                        'execution_error_message' => $item
                            ->execution_error_message,

                        'queued_at' => $item
                            ->queued_at
                            ?->toIso8601String(),

                        'started_at' => $item
                            ->started_at
                            ?->toIso8601String(),

                        'last_attempt_at' => $item
                            ->last_attempt_at
                            ?->toIso8601String(),

                        'completed_at' => $item
                            ->completed_at
                            ?->toIso8601String(),
                    ],
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     planned: int,
     *     queued: int,
     *     processing: int,
     *     completed: int,
     *     skipped: int,
     *     drifted: int,
     *     failed: int
     * }
     */
    private function executionSummary(
        AiSeoBulkJob $job,
    ): array {
        $summary = [
            'total' => 0,
            'planned' => 0,
            'queued' => 0,
            'processing' => 0,
            'completed' => 0,
            'skipped' => 0,
            'drifted' => 0,
            'failed' => 0,
        ];

        foreach (
            $job
                ->items as $item
        ) {
            $status =
                $item
                    ->execution_status
                    ->value;

            $summary[
                'total'
            ]++;

            $summary[
                $status
            ]++;
        }

        return $summary;
    }
}
