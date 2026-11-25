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

final class SeoPerformanceOReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_completed_snapshots_remain_isolated_and_produce_non_causal_cross_source_reporting(): void
    {
        $searchConnection = $this->connection(SeoPerformanceProvider::SearchConsole);
        $analyticsConnection = $this->connection(SeoPerformanceProvider::GoogleAnalytics);

        $searchRun = $this->completedRun(
            connection: $searchConnection,
            scope: SeoPerformanceSyncScope::SearchPerformance,
            from: '2026-11-20',
            to: '2026-11-21',
        );
        $analyticsRun = $this->completedRun(
            connection: $analyticsConnection,
            scope: SeoPerformanceSyncScope::LandingAnalytics,
            from: '2026-11-20',
            to: '2026-11-21',
        );

        $this->searchRow(
            run: $searchRun,
            date: '2026-11-20',
            url: 'https://example.com/products/red?variant=1#details',
            clicks: 12,
            impressions: 600,
            position: 4.0,
        );
        $this->analyticsRow(
            run: $analyticsRun,
            date: '2026-11-20',
            page: '/products/red?variant=1',
            sessions: 30,
            engagedSessions: 18,
            keyEvents: 3,
            revenue: 125.50,
        );

        // Later completed snapshots must not leak into an explicitly selected pair.
        $newerSearchRun = $this->completedRun(
            connection: $searchConnection,
            scope: SeoPerformanceSyncScope::SearchPerformance,
            from: '2026-11-20',
            to: '2026-11-21',
        );
        $newerAnalyticsRun = $this->completedRun(
            connection: $analyticsConnection,
            scope: SeoPerformanceSyncScope::LandingAnalytics,
            from: '2026-11-20',
            to: '2026-11-21',
        );
        $this->searchRow($newerSearchRun, '2026-11-20', 'https://example.com/leak', 999, 999, 1.0);
        $this->analyticsRow($newerAnalyticsRun, '2026-11-20', '/leak', 999, 999, 999, 999);

        $workspace = app(GetUnifiedSeoPerformanceDashboardQuery::class)->get(
            searchRunPublicId: $searchRun->public_id,
            analyticsRunPublicId: $analyticsRun->public_id,
            dateFrom: null,
            dateTo: null,
            pageSearch: null,
            pagesPage: 1,
        );

        self::assertSame(
            $searchRun->public_id,
            $workspace['selected_runs']['search_console']['public_id'],
        );
        self::assertSame(
            $analyticsRun->public_id,
            $workspace['selected_runs']['google_analytics']['public_id'],
        );
        self::assertSame(12.0, $workspace['summary']['search_console']['clicks']);
        self::assertSame(600.0, $workspace['summary']['search_console']['impressions']);
        self::assertSame(30.0, $workspace['summary']['google_analytics']['sessions']);
        self::assertSame(3.0, $workspace['summary']['google_analytics']['key_events']);
        self::assertSame(125.5, $workspace['summary']['google_analytics']['total_revenue']);

        $matched = collect($workspace['pages'])->firstWhere(
            'path',
            '/products/red?variant=1',
        );
        self::assertIsArray($matched);
        self::assertTrue($matched['has_search_console']);
        self::assertTrue($matched['has_google_analytics']);
        self::assertSame(12.0, $matched['search_clicks']);
        self::assertSame(30.0, $matched['analytics_sessions']);

        self::assertStringContainsString(
            'does not attribute GA4 sessions',
            $workspace['attribution_note'],
        );
        self::assertStringNotContainsString(
            'conversion rate from Search Console',
            $workspace['attribution_note'],
        );
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

    private function completedRun(
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
            'rows_received' => 1,
            'rows_persisted' => 1,
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
