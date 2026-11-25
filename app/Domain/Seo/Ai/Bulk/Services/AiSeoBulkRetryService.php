<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use Illuminate\Support\Facades\DB;

final readonly class AiSeoBulkRetryService
{
    public function __construct(
        private AiSeoBulkJobProgressService $progress,
    ) {}

    public function retryFailed(
        AiSeoBulkJob $job,
    ): int {
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
                        $locked->status ===
                        AiSeoBulkJobStatus::Cancelled
                    ) {
                        return [];
                    }

                    $now =
                        now();

                    $items =
                        AiSeoBulkJobItem::query()
                            ->where(
                                'ai_seo_bulk_job_id',
                                $locked->id,
                            )
                            ->where(
                                'execution_status',
                                AiSeoBulkItemExecutionStatus::Failed
                                    ->value,
                            )
                            ->lockForUpdate()
                            ->get();

                    $ids = [];

                    foreach ($items as $item) {
                        $item->update([
                            'execution_status' => AiSeoBulkItemExecutionStatus::Queued,

                            'queued_at' => $now,

                            'completed_at' => null,

                            'execution_error_code' => null,

                            'execution_error_message' => null,
                        ]);

                        $ids[] =
                            $item->id;
                    }

                    if ($ids !== []) {
                        $locked->update([
                            'status' => AiSeoBulkJobStatus::Running,

                            'started_at' => $locked->started_at
                                ?? $now,

                            'last_dispatched_at' => $now,

                            'completed_at' => null,
                        ]);
                    }

                    return $ids;
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

        $this
            ->progress
            ->refresh(
                $job->id,
            );

        return count(
            $itemIds,
        );
    }
}
