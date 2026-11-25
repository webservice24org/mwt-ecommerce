<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkDispatchService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkRetryService;
use App\Models\AiSeoBulkJob;
use Illuminate\Http\RedirectResponse;

final class AiSeoBulkExecutionController
{
    public function execute(
        string $bulkJobId,
        AiSeoBulkDispatchService $dispatch,
    ): RedirectResponse {
        $job =
            $this->job(
                $bulkJobId,
            );

        $dispatch->dispatch(
            $job,
        );

        return back()->with(
            'success',
            'Bulk AI execution was queued. Refresh this plan to monitor item progress.',
        );
    }

    public function retryFailed(
        string $bulkJobId,
        AiSeoBulkRetryService $retry,
    ): RedirectResponse {
        $job =
            $this->job(
                $bulkJobId,
            );

        $count =
            $retry->retryFailed(
                $job,
            );

        return back()->with(
            'success',
            $count === 0
                ? 'There are no failed bulk AI items to retry.'
                : sprintf(
                    '%d failed bulk AI item(s) were re-queued.',
                    $count,
                ),
        );
    }

    private function job(
        string $bulkJobId,
    ): AiSeoBulkJob {
        if (
            ! ctype_digit(
                $bulkJobId,
            )
            || (int) $bulkJobId < 1
        ) {
            abort(
                404,
            );
        }

        return AiSeoBulkJob::query()
            ->findOrFail(
                (int) $bulkJobId,
            );
    }
}
