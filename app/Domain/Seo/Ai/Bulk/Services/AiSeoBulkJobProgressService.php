<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use Illuminate\Support\Facades\DB;

final readonly class AiSeoBulkJobProgressService
{
    public function refreshForItem(
        int $itemId,
    ): void {
        $jobId =
            AiSeoBulkJobItem::query()
                ->whereKey($itemId)
                ->value(
                    'ai_seo_bulk_job_id',
                );

        if (! is_int($jobId)) {
            return;
        }

        $this->refresh($jobId);
    }

    public function refresh(
        int|AiSeoBulkJob $job,
    ): AiSeoBulkJob {
        $jobId =
            $job instanceof AiSeoBulkJob
                ? $job->id
                : $job;

        return DB::transaction(
            function () use (
                $jobId,
            ): AiSeoBulkJob {
                $locked =
                    AiSeoBulkJob::query()
                        ->lockForUpdate()
                        ->findOrFail($jobId);

                if (
                    $locked->status ===
                    AiSeoBulkJobStatus::Cancelled
                ) {
                    return $locked;
                }

                $counts = [
                    'total' => 0,
                    'planned' => 0,
                    'queued' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'skipped' => 0,
                    'drifted' => 0,
                    'failed' => 0,
                ];

                $rows =
                    AiSeoBulkJobItem::query()
                        ->where(
                            'ai_seo_bulk_job_id',
                            $locked->id,
                        )
                        ->selectRaw(
                            'execution_status, COUNT(*) as aggregate',
                        )
                        ->groupBy(
                            'execution_status',
                        )
                        ->get();

                foreach ($rows as $row) {
                    $rawStatus =
                        $row
                            ->getRawOriginal(
                                'execution_status',
                            );

                    $status =
                        is_string(
                            $rawStatus,
                        )
                            ? $rawStatus
                            : '';

                    $count =
                        (int) $row
                            ->getAttribute(
                                'aggregate',
                            );

                    if (
                        array_key_exists(
                            $status,
                            $counts,
                        )
                    ) {
                        $counts[$status] =
                            $count;
                    }

                    $counts['total'] +=
                        $count;
                }

                $terminal =
                    $counts['completed']
                    + $counts['skipped']
                    + $counts['drifted']
                    + $counts['failed'];

                $finished =
                    $counts['total'] > 0
                    && $terminal ===
                        $counts['total'];

                $status =
                    $finished
                        ? (
                            $counts['failed'] > 0
                                ? AiSeoBulkJobStatus::CompletedWithErrors
                                : AiSeoBulkJobStatus::Completed
                        )
                        : AiSeoBulkJobStatus::Running;

                $updates = [
                    'status' => $status,
                    'execution_summary' => $counts,
                ];

                if (
                    $locked->started_at ===
                    null
                ) {
                    $updates['started_at'] =
                        now();
                }

                if ($finished) {
                    $updates['completed_at'] =
                        $locked->completed_at
                        ?? now();
                } else {
                    $updates['completed_at'] =
                        null;
                }

                $locked->update($updates);

                return $locked->refresh();
            },
        );
    }
}
