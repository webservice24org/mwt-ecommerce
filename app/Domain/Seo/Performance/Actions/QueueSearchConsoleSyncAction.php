<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Actions;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Jobs\Seo\Performance\ProcessSearchConsoleSyncRun;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use DomainException;

final class QueueSearchConsoleSyncAction
{
    public function execute(SeoPerformanceSyncRun $run): void
    {
        $current = SeoPerformanceSyncRun::query()->findOrFail($run->getKey());
        $connection = SeoPerformanceConnection::query()->findOrFail(
            $current->seo_performance_connection_id,
        );

        if ($connection->provider !== SeoPerformanceProvider::SearchConsole) {
            throw new DomainException('Only Google Search Console synchronization runs may use this queue action.');
        }

        if ($current->scope !== SeoPerformanceSyncScope::SearchPerformance) {
            throw new DomainException('Only Search Console search-performance runs may use this queue action.');
        }

        if (! in_array($current->status, [
            SeoPerformanceSyncStatus::Queued,
            SeoPerformanceSyncStatus::Running,
        ], true)) {
            throw new DomainException('Only queued or running Search Console synchronizations may be dispatched.');
        }

        ProcessSearchConsoleSyncRun::dispatch($current->getKey())
            ->onQueue($this->queueName())
            ->afterCommit();
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
