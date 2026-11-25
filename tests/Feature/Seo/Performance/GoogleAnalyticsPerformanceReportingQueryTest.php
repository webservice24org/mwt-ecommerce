<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Queries\GetGoogleAnalyticsPerformanceReportQuery;
use App\Models\SeoGoogleAnalyticsLandingPageRow;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GoogleAnalyticsPerformanceReportingQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_one_completed_ga4_snapshot_and_additive_summary_metrics(): void
    {
        $connection = $this->connection();
        $selected = $this->completedRun($connection, '2026-11-20', '2026-11-21');
        $other = $this->completedRun($connection, '2026-11-20', '2026-11-21');

        $this->row($selected, '2026-11-20', '/home', 10, 6, 8, 4, 2, 100);
        $this->row($selected, '2026-11-21', '/home', 20, 10, 12, 5, 3, 150);
        $this->row($selected, '2026-11-20', '/products', 30, 15, 20, 10, 4, 200);
        $this->row($other, '2026-11-20', '/leak', 999, 999, 999, 999, 999, 999);

        $workspace = app(GetGoogleAnalyticsPerformanceReportQuery::class)->get(
            runPublicId: $selected->public_id,
            dateFrom: null,
            dateTo: null,
            pageSearch: null,
            runsPage: 1,
            pagesPage: 1,
        );

        self::assertSame($selected->public_id, $workspace['selected_run']['public_id']);
        self::assertSame(60.0, $workspace['summary']['sessions']);
        self::assertSame(31.0, $workspace['summary']['engaged_sessions']);
        self::assertEqualsWithDelta(31 / 60, $workspace['summary']['engagement_rate'], 0.000001);
        self::assertSame(9.0, $workspace['summary']['key_events']);
        self::assertSame(450.0, $workspace['summary']['total_revenue']);
        self::assertCount(2, $workspace['daily']);
        self::assertCount(2, $workspace['pages']);

        self::assertSame('/home', $workspace['pages'][0]['landing_page']);
        self::assertSame(30.0, $workspace['pages'][0]['sessions']);
        self::assertSame(20.0, $workspace['pages'][0]['active_users_row_sum']);
        self::assertSame(9.0, $workspace['pages'][0]['new_users_row_sum']);
        self::assertSame(2, $workspace['pages_pagination']['total']);
    }

    public function test_report_date_and_page_search_filters_are_inclusive_and_do_not_cross_runs(): void
    {
        $connection = $this->connection();
        $run = $this->completedRun($connection, '2026-11-20', '2026-11-21');

        $this->row($run, '2026-11-20', '/products/red', 5, 4, 4, 2, 1, 50);
        $this->row($run, '2026-11-20', '/category/blue', 9, 5, 7, 3, 2, 75);
        $this->row($run, '2026-11-21', '/products/red', 11, 8, 9, 4, 3, 125);

        $workspace = app(GetGoogleAnalyticsPerformanceReportQuery::class)->get(
            runPublicId: $run->public_id,
            dateFrom: '2026-11-20',
            dateTo: '2026-11-20',
            pageSearch: 'products',
            runsPage: 1,
            pagesPage: 1,
        );

        self::assertSame(14.0, $workspace['summary']['sessions']);
        self::assertSame(9.0, $workspace['summary']['engaged_sessions']);
        self::assertCount(1, $workspace['daily']);
        self::assertCount(1, $workspace['pages']);
        self::assertSame('/products/red', $workspace['pages'][0]['landing_page']);
        self::assertSame(5.0, $workspace['pages'][0]['sessions']);
    }

    private function connection(): SeoPerformanceConnection
    {
        return SeoPerformanceConnection::query()->create([
            'public_id' => Str::uuid()->toString(),
            'provider' => SeoPerformanceProvider::GoogleAnalytics,
            'status' => SeoPerformanceConnectionStatus::Active,
            'display_name' => 'Primary GA4',
            'property_identifier' => '123456789',
            'property_url' => 'https://example.com/',
            'timezone' => 'UTC',
            'capabilities' => ['analytics_landing_pages'],
            'verified_at' => now(),
        ]);
    }

    private function completedRun(
        SeoPerformanceConnection $connection,
        string $from,
        string $to,
    ): SeoPerformanceSyncRun {
        return SeoPerformanceSyncRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'seo_performance_connection_id' => $connection->id,
            'scope' => SeoPerformanceSyncScope::LandingAnalytics,
            'mode' => SeoPerformanceSyncMode::Manual,
            'status' => SeoPerformanceSyncStatus::Completed,
            'date_from' => $from,
            'date_to' => $to,
            'attempt_count' => 1,
            'rows_received' => 3,
            'rows_persisted' => 3,
            'provider_complete' => true,
            'provider_truncated' => false,
            'queue_pass_count' => 2,
            'orchestration_version' => 'google-analytics-sync-v1',
            'queued_at' => now()->subMinutes(2),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }

    private function row(
        SeoPerformanceSyncRun $run,
        string $date,
        string $landingPage,
        float $sessions,
        float $engagedSessions,
        float $activeUsers,
        float $newUsers,
        float $keyEvents,
        float $revenue,
    ): void {
        SeoGoogleAnalyticsLandingPageRow::query()->create([
            'seo_performance_sync_run_id' => $run->id,
            'seo_performance_connection_id' => $run->seo_performance_connection_id,
            'data_date' => $date,
            'landing_page' => $landingPage,
            'landing_page_hash' => hash('sha256', $landingPage),
            'sessions' => $sessions,
            'engaged_sessions' => $engagedSessions,
            'active_users' => $activeUsers,
            'new_users' => $newUsers,
            'key_events' => $keyEvents,
            'total_revenue' => $revenue,
        ]);
    }
}
