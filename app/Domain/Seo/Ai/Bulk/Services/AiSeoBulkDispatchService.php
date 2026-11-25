<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkPlannedAction;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use Illuminate\Support\Facades\DB;

final readonly class AiSeoBulkDispatchService
{
    public function __construct(
        private AiSeoBulkJobProgressService $progress,
    ) {}

    public function dispatch(
        AiSeoBulkJob $job,
    ): AiSeoBulkJob {
        $itemIds =
            DB::transaction(
                function () use (
                    $job,
                ): array {
                    $locked =
                        AiSeoBulkJob::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $job->id,
                            );

                    if (
                        in_array(
                            $locked->status,
                            [
                                AiSeoBulkJobStatus::Completed,
                                AiSeoBulkJobStatus::CompletedWithErrors,
                                AiSeoBulkJobStatus::Cancelled,
                            ],
                            true,
                        )
                    ) {
                        return [];
                    }

                    $now =
                        now();

                    $staleProcessingCutoff =
                        $now
                            ->copy()
                            ->subSeconds(
                                max(
                                    60,
                                    (int) config(
                                        'ai-seo-bulk.processing_stale_seconds',
                                        900,
                                    ),
                                ),
                            );

                    $items =
                        AiSeoBulkJobItem::query()
                            ->where(
                                'ai_seo_bulk_job_id',
                                $locked->id,
                            )
                            ->lockForUpdate()
                            ->get();

                    $dispatchIds = [];

                    foreach ($items as $item) {
                        if (
                            $item->planned_action ===
                            AiSeoBulkPlannedAction::Skip
                        ) {
                            if (
                                ! $item
                                    ->execution_status
                                    ->terminal()
                            ) {
                                $item->update([
                                    'execution_status' => AiSeoBulkItemExecutionStatus::Skipped,

                                    'execution_error_code' => null,

                                    'execution_error_message' => null,

                                    'completed_at' => $now,
                                ]);
                            }

                            continue;
                        }

                        if (
                            $item->execution_status ===
                            AiSeoBulkItemExecutionStatus::Planned
                        ) {
                            $item->update([
                                'execution_status' => AiSeoBulkItemExecutionStatus::Queued,

                                'queued_at' => $now,

                                'execution_error_code' => null,

                                'execution_error_message' => null,

                                'completed_at' => null,
                            ]);

                            $dispatchIds[] =
                                $item->id;

                            continue;
                        }

                        if (
                            $item->execution_status ===
                            AiSeoBulkItemExecutionStatus::Queued
                        ) {
                            $dispatchIds[] =
                                $item->id;

                            continue;
                        }

                        if (
                            $item->execution_status ===
                            AiSeoBulkItemExecutionStatus::Processing
                            && (
                                $item->last_attempt_at ===
                                null
                                || $item
                                    ->last_attempt_at
                                    ->lessThan(
                                        $staleProcessingCutoff,
                                    )
                            )
                        ) {
                            $item->update([
                                'execution_status' => AiSeoBulkItemExecutionStatus::Queued,

                                'queued_at' => $now,

                                'execution_error_code' => 'processing_recovered',

                                'execution_error_message' => 'A stale processing item was re-queued for idempotent recovery.',

                                'completed_at' => null,
                            ]);

                            $dispatchIds[] =
                                $item->id;
                        }
                    }

                    $locked->update([
                        'status' => AiSeoBulkJobStatus::Running,

                        'started_at' => $locked->started_at
                            ?? $now,

                        'last_dispatched_at' => $now,

                        'completed_at' => null,
                    ]);

                    return $dispatchIds;
                },
            );

        foreach ($itemIds as $itemId) {
            AiSeoBulkItemExecutionJob::dispatch(
                $itemId,
            )->onQueue(
                (string) config(
                    'ai-seo-bulk.queue',
                    'ai-seo',
                ),
            );
        }

        return $this
            ->progress
            ->refresh(
                $job->id,
            );
    }
}
