<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\GoogleAnalyticsSyncAdvanceData;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Providers\GoogleAnalyticsProviderAdapter;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class GoogleAnalyticsSyncOrchestrator
{
    public const VERSION = 'google-analytics-sync-v1';

    public const ROWS_PER_QUEUE_PASS = 1000;

    public const MAX_QUEUE_PASSES = 30;

    public function __construct(
        private GoogleAnalyticsProviderAdapter $provider,
        private GoogleAnalyticsLandingPageWriter $writer,
        private SeoPerformanceSyncLifecycle $lifecycle,
    ) {}

    public function advance(SeoPerformanceSyncRun $run): GoogleAnalyticsSyncAdvanceData
    {
        $current = SeoPerformanceSyncRun::query()->findOrFail($run->getKey());

        if ($current->status === SeoPerformanceSyncStatus::Completed) {
            return $this->result($current, true);
        }

        if ($current->status === SeoPerformanceSyncStatus::Failed) {
            throw new DomainException('Failed Google Analytics synchronization runs cannot be resumed.');
        }

        $connection = SeoPerformanceConnection::query()->findOrFail(
            $current->seo_performance_connection_id,
        );

        if ($connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
            throw new DomainException('This synchronization is not attached to Google Analytics.');
        }

        if ($current->scope !== SeoPerformanceSyncScope::LandingAnalytics) {
            throw new DomainException('This synchronization is not a Google Analytics landing-page import.');
        }

        if ($current->status === SeoPerformanceSyncStatus::Queued) {
            $current = $this->lifecycle->start($current);
        }

        if ($current->provider_complete) {
            $completed = $this->lifecycle->complete(
                $current,
                $current->rows_received,
                $current->rows_persisted,
            );

            return $this->result($completed, true);
        }

        $current = $this->beginQueuePass($current);

        $page = $this->provider->fetchPage(
            $connection,
            new SeoPerformanceFetchRequestData(
                scope: SeoPerformanceSyncScope::LandingAnalytics,
                dateFrom: new DateTimeImmutable($current->date_from->format('Y-m-d')),
                dateTo: new DateTimeImmutable($current->date_to->format('Y-m-d')),
                rowLimit: self::ROWS_PER_QUEUE_PASS,
                pageToken: $current->provider_page_token,
            ),
        );

        if (! $page->complete && ($page->nextPageToken === null || $page->nextPageToken === '')) {
            throw new DomainException('Google Analytics returned an incomplete page without a continuation token.');
        }

        if ($page->complete && $page->nextPageToken !== null) {
            throw new DomainException('Google Analytics returned a completed page with a continuation token.');
        }

        $persisted = $this->writer->persist($current, $page);
        $current = $this->recordProgress(
            run: $current,
            rowsReceived: count($page->rows),
            rowsPersisted: $persisted,
            nextPageToken: $page->nextPageToken,
            providerComplete: $page->complete,
            truncated: $page->truncated,
        );

        if (! $current->provider_complete) {
            return $this->result($current, false);
        }

        $completed = $this->lifecycle->complete(
            $current,
            $current->rows_received,
            $current->rows_persisted,
        );

        return $this->result($completed, true);
    }

    private function beginQueuePass(SeoPerformanceSyncRun $run): SeoPerformanceSyncRun
    {
        return DB::transaction(function () use ($run): SeoPerformanceSyncRun {
            $current = SeoPerformanceSyncRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoPerformanceSyncStatus::Running) {
                throw new DomainException('Google Analytics queue orchestration requires a running synchronization.');
            }

            if (
                $current->orchestration_version !== null
                && $current->orchestration_version !== self::VERSION
            ) {
                throw new DomainException('Google Analytics synchronization orchestration version changed during an active run.');
            }

            $nextPass = $current->queue_pass_count + 1;
            if ($nextPass > self::MAX_QUEUE_PASSES) {
                throw new DomainException('Google Analytics synchronization queue pass limit reached before completion.');
            }

            $current->forceFill([
                'orchestration_version' => self::VERSION,
                'queue_pass_count' => $nextPass,
                'last_progress_at' => now(),
            ])->save();

            return $current->refresh();
        });
    }

    private function recordProgress(
        SeoPerformanceSyncRun $run,
        int $rowsReceived,
        int $rowsPersisted,
        ?string $nextPageToken,
        bool $providerComplete,
        bool $truncated,
    ): SeoPerformanceSyncRun {
        if ($rowsReceived < 0 || $rowsPersisted < 0 || $rowsPersisted > $rowsReceived) {
            throw new DomainException('Google Analytics queue progress row counts are invalid.');
        }

        return DB::transaction(function () use (
            $run,
            $rowsReceived,
            $rowsPersisted,
            $nextPageToken,
            $providerComplete,
            $truncated,
        ): SeoPerformanceSyncRun {
            $current = SeoPerformanceSyncRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoPerformanceSyncStatus::Running) {
                throw new DomainException('Google Analytics synchronization stopped while page progress was being recorded.');
            }

            $current->forceFill([
                'provider_page_token' => $nextPageToken,
                'provider_complete' => $providerComplete,
                'provider_truncated' => $current->provider_truncated || $truncated,
                'rows_received' => $current->rows_received + $rowsReceived,
                'rows_persisted' => $current->rows_persisted + $rowsPersisted,
                'last_progress_at' => now(),
            ])->save();

            return $current->refresh();
        });
    }

    private function result(
        SeoPerformanceSyncRun $run,
        bool $completed,
    ): GoogleAnalyticsSyncAdvanceData {
        return new GoogleAnalyticsSyncAdvanceData(
            completed: $completed,
            queuePass: $run->queue_pass_count,
            rowsReceived: $run->rows_received,
            rowsPersisted: $run->rows_persisted,
            truncated: $run->provider_truncated,
        );
    }
}
