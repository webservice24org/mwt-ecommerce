<?php

declare(strict_types=1);

namespace App\Jobs\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Services\SearchConsoleSyncOrchestrator;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessSearchConsoleSyncRun implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 180;

    public function __construct(public readonly int $runId) {}

    public function uniqueId(): string
    {
        return 'search-console-sync:'.$this->runId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('search-console-sync:'.$this->runId))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(SearchConsoleSyncOrchestrator $orchestrator): void
    {
        $run = SeoPerformanceSyncRun::query()->find($this->runId);

        if (
            $run === null
            || $run->status === SeoPerformanceSyncStatus::Completed
            || $run->status === SeoPerformanceSyncStatus::Failed
        ) {
            return;
        }

        $step = $orchestrator->advance($run);
        if ($step->completed) {
            return;
        }

        self::dispatch($this->runId)
            ->onQueue($this->queueName())
            ->delay(now()->addSeconds(1));
    }

    public function failed(Throwable $exception): void
    {
        $run = SeoPerformanceSyncRun::query()->find($this->runId);

        if (
            $run === null
            || $run->status === SeoPerformanceSyncStatus::Completed
            || $run->status === SeoPerformanceSyncStatus::Failed
        ) {
            return;
        }

        $lifecycle = app(SeoPerformanceSyncLifecycle::class);

        try {
            if ($run->provider_complete && $run->status === SeoPerformanceSyncStatus::Running) {
                $lifecycle->complete(
                    $run,
                    $run->rows_received,
                    $run->rows_persisted,
                );

                return;
            }

            $lifecycle->fail(
                $run,
                'search_console_sync_job_failed',
                'Search Console synchronization failed after queue retries.',
            );
        } catch (Throwable) {
            // Preserve the queue failure as the primary exception.
        }
    }

    private function queueName(): string
    {
        $queue = trim((string) config(
            'seo_performance.search_console.queue',
            'seo-performance',
        ));

        return $queue === '' ? 'seo-performance' : $queue;
    }
}
