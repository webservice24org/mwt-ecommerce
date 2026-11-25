<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Actions;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Jobs\Seo\Performance\ProcessGoogleAnalyticsSyncRun;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use DomainException;

final class QueueGoogleAnalyticsSyncAction
{
    public function execute(SeoPerformanceSyncRun $run): void
    {
        $current = SeoPerformanceSyncRun::query()->findOrFail($run->getKey());
        $connection = SeoPerformanceConnection::query()->findOrFail(
            $current->seo_performance_connection_id,
        );

        if ($connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
            throw new DomainException('Only Google Analytics synchronization runs may use this queue action.');
        }

        if ($current->scope !== SeoPerformanceSyncScope::LandingAnalytics) {
            throw new DomainException('Only Google Analytics landing-page runs may use this queue action.');
        }

        if (! in_array($current->status, [
            SeoPerformanceSyncStatus::Queued,
            SeoPerformanceSyncStatus::Running,
        ], true)) {
            throw new DomainException('Only queued or running Google Analytics synchronizations may be dispatched.');
        }

        ProcessGoogleAnalyticsSyncRun::dispatch($current->getKey())
            ->onQueue($this->queueName())
            ->afterCommit();
    }

    private function queueName(): string
    {
        $queue = trim((string) config(
            'seo_performance_google_analytics.queue',
            'seo-performance',
        ));

        return $queue === '' ? 'seo-performance' : $queue;
    }
}
