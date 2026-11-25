<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Queries\GetUnifiedSeoPerformanceDashboardQuery;
use App\Models\SeoGoogleAnalyticsLandingPageRow;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use App\Models\SeoSearchConsolePerformanceRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class UnifiedSeoPerformanceDashboardQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_compares_one_completed_snapshot_per_source_without_causal_attribution(): void
    {
        $searchConnection = $this->connection(SeoPerformanceProvider::SearchConsole);
        $analyticsConnection = $this->connection(SeoPerformanceProvider::GoogleAnalytics);

        $searchRun = $this->makeSyncRun(
            $searchConnection,
            SeoPerformanceSyncScope::SearchPerformance,
            '2026-11-20',
            '2026-11-21',
        );
        $analyticsRun = $this->makeSyncRun(
            $analyticsConnection,
            SeoPerformanceSyncScope::LandingAnalytics,
            '2026-11-20',
            '2026-11-21',
        );

        $this->searchRow($searchRun, '2026-11-20', 'https://example.com/products/red', 10, 1000, 4.0);
        $this->searchRow($searchRun, '2026-11-21', 'https://example.com/search-only', 5, 200, 8.0);
        $this->analyticsRow($analyticsRun, '2026-11-20', '/products/red', 20, 8, 2, 50);
        $this->analyticsRow($analyticsRun, '2026-11-21', '/analytics-only', 12, 9, 1, 25);

        $workspace = app(GetUnifiedSeoPerformanceDashboardQuery::class)->get(
            searchRunPublicId: $searchRun->public_id,
            analyticsRunPublicId: $analyticsRun->public_id,
            dateFrom: null,
            dateTo: null,
            pageSearch: null,
            pagesPage: 1,
        );

        self::assertTrue($workspace['date_range']['has_overlap']);
        self::assertSame(15.0, $workspace['summary']['search_console']['clicks']);
        self::assertSame(1200.0, $workspace['summary']['search_console']['impressions']);
        self::assertSame(32.0, $workspace['summary']['google_analytics']['sessions']);
        self::assertCount(2, $workspace['daily']);

        $matched = collect($workspace['pages'])->firstWhere('path', '/products/red');
        self::assertIsArray($matched);
        self::assertTrue($matched['has_search_console']);
        self::assertTrue($matched['has_google_analytics']);
        self::assertSame(10.0, $matched['search_clicks']);
        self::assertSame(20.0, $matched['analytics_sessions']);

        self::assertTrue(
            collect($workspace['insights'])
                ->contains(fn (array $insight): bool => $insight['type'] === 'high_impressions_low_ctr'),
        );
        self::assertStringContainsString(
            'does not attribute GA4 sessions',
            $workspace['attribution_note'],
        );
    }

    public function test_dashboard_clamps_filters_to_the_overlap_of_selected_snapshots(): void
    {
        $searchConnection = $this->connection(SeoPerformanceProvider::SearchConsole);
        $analyticsConnection = $this->connection(SeoPerformanceProvider::GoogleAnalytics);

        $searchRun = $this->makeSyncRun(
            $searchConnection,
            SeoPerformanceSyncScope::SearchPerformance,
            '2026-11-20',
            '2026-11-22',
        );
        $analyticsRun = $this->makeSyncRun(
            $analyticsConnection,
            SeoPerformanceSyncScope::LandingAnalytics,
            '2026-11-21',
            '2026-11-23',
        );

        $this->searchRow($searchRun, '2026-11-20', 'https://example.com/outside', 99, 999, 1.0);
        $this->searchRow($searchRun, '2026-11-21', 'https://example.com/inside', 4, 40, 2.0);
        $this->analyticsRow($analyticsRun, '2026-11-21', '/inside', 7, 5, 1, 10);
        $this->analyticsRow($analyticsRun, '2026-11-23', '/outside', 77, 70, 9, 90);

        $workspace = app(GetUnifiedSeoPerformanceDashboardQuery::class)->get(
            searchRunPublicId: $searchRun->public_id,
            analyticsRunPublicId: $analyticsRun->public_id,
            dateFrom: '2026-11-19',
            dateTo: '2026-11-24',
            pageSearch: 'inside',
            pagesPage: 1,
        );

        self::assertSame('2026-11-21', $workspace['date_range']['from']);
        self::assertSame('2026-11-22', $workspace['date_range']['to']);
        self::assertSame(4.0, $workspace['summary']['search_console']['clicks']);
        self::assertSame(7.0, $workspace['summary']['google_analytics']['sessions']);
        self::assertCount(1, $workspace['pages']);
        self::assertSame('/inside', $workspace['pages'][0]['path']);
    }

    private function connection(SeoPerformanceProvider $provider): SeoPerformanceConnection
    {
        return SeoPerformanceConnection::query()->create([
            'public_id' => Str::uuid()->toString(),
            'provider' => $provider,
            'status' => SeoPerformanceConnectionStatus::Active,
            'display_name' => $provider === SeoPerformanceProvider::SearchConsole
                ? 'Primary Search Console'
                : 'Primary GA4',
            'property_identifier' => $provider === SeoPerformanceProvider::SearchConsole
                ? 'sc-domain:example.com'
                : '123456789',
            'property_url' => 'https://example.com/',
            'timezone' => 'UTC',
            'capabilities' => $provider === SeoPerformanceProvider::SearchConsole
                ? ['search_queries', 'search_landing_pages']
                : ['analytics_landing_pages'],
            'verified_at' => now(),
        ]);
    }

    private function makeSyncRun(
        SeoPerformanceConnection $connection,
        SeoPerformanceSyncScope $scope,
        string $from,
        string $to,
    ): SeoPerformanceSyncRun {
        return SeoPerformanceSyncRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'seo_performance_connection_id' => $connection->id,
            'scope' => $scope,
            'mode' => SeoPerformanceSyncMode::Manual,
            'status' => SeoPerformanceSyncStatus::Completed,
            'date_from' => $from,
            'date_to' => $to,
            'attempt_count' => 1,
            'rows_received' => 2,
            'rows_persisted' => 2,
            'provider_complete' => true,
            'provider_truncated' => false,
            'queue_pass_count' => 1,
            'orchestration_version' => $scope === SeoPerformanceSyncScope::SearchPerformance
                ? 'search-console-sync-v1'
                : 'google-analytics-sync-v1',
            'completed_at' => now(),
        ]);
    }

    private function searchRow(
        SeoPerformanceSyncRun $run,
        string $date,
        string $url,
        float $clicks,
        float $impressions,
        float $position,
    ): void {
        SeoSearchConsolePerformanceRow::query()->create([
            'seo_performance_sync_run_id' => $run->id,
            'seo_performance_connection_id' => $run->seo_performance_connection_id,
            'dimension_type' => SearchConsolePerformanceDimension::LandingPage,
            'search_type' => 'web',
            'data_date' => $date,
            'dimension_value' => $url,
            'dimension_hash' => hash('sha256', $url),
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
            'position' => $position,
        ]);
    }

    private function analyticsRow(
        SeoPerformanceSyncRun $run,
        string $date,
        string $page,
        float $sessions,
        float $engagedSessions,
        float $keyEvents,
        float $revenue,
    ): void {
        SeoGoogleAnalyticsLandingPageRow::query()->create([
            'seo_performance_sync_run_id' => $run->id,
            'seo_performance_connection_id' => $run->seo_performance_connection_id,
            'data_date' => $date,
            'landing_page' => $page,
            'landing_page_hash' => hash('sha256', $page),
            'sessions' => $sessions,
            'engaged_sessions' => $engagedSessions,
            'active_users' => $sessions,
            'new_users' => 0,
            'key_events' => $keyEvents,
            'total_revenue' => $revenue,
        ]);
    }
}
