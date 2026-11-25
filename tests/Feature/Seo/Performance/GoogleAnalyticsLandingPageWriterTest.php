<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsLandingPageWriter;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GoogleAnalyticsLandingPageWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_rows_are_persisted_idempotently_for_one_sync_snapshot(): void
    {
        $connection = app(SeoPerformanceConnectionManager::class)->register(
            SeoPerformanceProvider::GoogleAnalytics,
            'GA4 Production',
            '123456789',
            null,
            'UTC',
        );
        $connection->forceFill([
            'status' => SeoPerformanceConnectionStatus::Active,
            'verified_at' => now(),
        ])->save();
        $connection = $connection->refresh();

        $lifecycle = app(SeoPerformanceSyncLifecycle::class);
        $run = $lifecycle->request(
            $connection,
            SeoPerformanceSyncScope::LandingAnalytics,
            SeoPerformanceSyncMode::Manual,
            new DateTimeImmutable('2026-11-20'),
            new DateTimeImmutable('2026-11-21'),
        );
        $run = $lifecycle->start($run);

        $page = new SeoPerformanceFetchPageData(
            rows: [[
                'date' => '2026-11-20',
                'landing_page' => '/products/red-shoes',
                'sessions' => 12.0,
                'engaged_sessions' => 9.0,
                'active_users' => 10.0,
                'new_users' => 4.0,
                'key_events' => 2.0,
                'total_revenue' => 1250.5,
            ]],
            nextPageToken: null,
            complete: true,
        );

        $writer = app(GoogleAnalyticsLandingPageWriter::class);
        self::assertSame(1, $writer->persist($run, $page));
        self::assertSame(1, $writer->persist($run, $page));

        $this->assertDatabaseCount('seo_google_analytics_landing_page_rows', 1);
        $this->assertDatabaseHas('seo_google_analytics_landing_page_rows', [
            'seo_performance_sync_run_id' => $run->getKey(),
            'data_date' => '2026-11-20',
            'landing_page_hash' => hash('sha256', '/products/red-shoes'),
            'sessions' => '12.0000',
            'engaged_sessions' => '9.0000',
            'total_revenue' => '1250.500000',
        ]);
    }
}
