<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Queries\GetSearchConsolePerformanceReportQuery;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use App\Models\SeoSearchConsolePerformanceRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SearchConsolePerformanceReportingQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_one_completed_snapshot_and_separates_query_and_page_dimensions(): void
    {
        $connection = $this->connection();
        $older = $this->completedRun($connection, '2026-11-01', '2026-11-02');
        $selected = $this->completedRun($connection, '2026-11-20', '2026-11-21');

        $this->row($older, SearchConsolePerformanceDimension::LandingPage, '2026-11-01', 'https://example.com/old', 999, 9999, 0.10, 1.0);
        $this->row($selected, SearchConsolePerformanceDimension::LandingPage, '2026-11-20', 'https://example.com/a', 10, 100, 0.10, 4.0);
        $this->row($selected, SearchConsolePerformanceDimension::LandingPage, '2026-11-21', 'https://example.com/b', 20, 400, 0.05, 8.0);
        $this->row($selected, SearchConsolePerformanceDimension::Query, '2026-11-20', 'laravel shop', 7, 70, 0.10, 3.0);
        $this->row($selected, SearchConsolePerformanceDimension::Query, '2026-11-21', 'ecommerce bd', 3, 30, 0.10, 9.0);

        $workspace = app(GetSearchConsolePerformanceReportQuery::class)->get(
            runPublicId: $selected->public_id,
            dateFrom: null,
            dateTo: null,
            querySearch: null,
            pageSearch: null,
            runsPage: 1,
            queriesPage: 1,
            pagesPage: 1,
        );

        self::assertSame($selected->public_id, $workspace['selected_run']['public_id']);
        self::assertSame(30.0, $workspace['summary']['clicks']);
        self::assertSame(500.0, $workspace['summary']['impressions']);
        self::assertSame(0.06, $workspace['summary']['ctr']);
        self::assertSame(7.2, $workspace['summary']['position']);
        self::assertCount(2, $workspace['daily']);
        self::assertSame('laravel shop', $workspace['queries'][0]['value']);
        self::assertSame('https://example.com/b', $workspace['pages'][0]['value']);
        self::assertSame(2, $workspace['queries_pagination']['total']);
        self::assertSame(2, $workspace['pages_pagination']['total']);
    }

    public function test_report_filters_date_and_dimension_search_without_cross_run_leakage(): void
    {
        $connection = $this->connection();
        $run = $this->completedRun($connection, '2026-11-20', '2026-11-21');

        $this->row($run, SearchConsolePerformanceDimension::LandingPage, '2026-11-20', 'https://example.com/products/red', 5, 50, 0.10, 2.0);
        $this->row($run, SearchConsolePerformanceDimension::LandingPage, '2026-11-21', 'https://example.com/category/blue', 9, 90, 0.10, 6.0);
        $this->row($run, SearchConsolePerformanceDimension::Query, '2026-11-20', 'red shoes', 4, 40, 0.10, 3.0);
        $this->row($run, SearchConsolePerformanceDimension::Query, '2026-11-20', 'blue shoes', 6, 60, 0.10, 5.0);

        $workspace = app(GetSearchConsolePerformanceReportQuery::class)->get(
            runPublicId: $run->public_id,
            dateFrom: '2026-11-20',
            dateTo: '2026-11-20',
            querySearch: 'red',
            pageSearch: 'products',
            runsPage: 1,
            queriesPage: 1,
            pagesPage: 1,
        );

        self::assertSame(5.0, $workspace['summary']['clicks']);
        self::assertCount(1, $workspace['daily']);
        self::assertCount(1, $workspace['queries']);
        self::assertSame('red shoes', $workspace['queries'][0]['value']);
        self::assertCount(1, $workspace['pages']);
        self::assertSame('https://example.com/products/red', $workspace['pages'][0]['value']);
    }

    private function connection(): SeoPerformanceConnection
    {
        return SeoPerformanceConnection::query()->create([
            'public_id' => Str::uuid()->toString(),
            'provider' => SeoPerformanceProvider::SearchConsole,
            'status' => SeoPerformanceConnectionStatus::Active,
            'display_name' => 'Primary Search Console',
            'property_identifier' => 'sc-domain:example.com',
            'property_url' => 'https://example.com/',
            'timezone' => 'UTC',
            'capabilities' => ['search_queries', 'search_landing_pages'],
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
            'scope' => SeoPerformanceSyncScope::SearchPerformance,
            'mode' => SeoPerformanceSyncMode::Manual,
            'status' => SeoPerformanceSyncStatus::Completed,
            'date_from' => $from,
            'date_to' => $to,
            'attempt_count' => 1,
            'rows_received' => 4,
            'rows_persisted' => 4,
            'provider_complete' => true,
            'provider_truncated' => false,
            'queue_pass_count' => 2,
            'orchestration_version' => 'search-console-sync-v1',
            'queued_at' => now()->subMinutes(2),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }

    private function row(
        SeoPerformanceSyncRun $run,
        SearchConsolePerformanceDimension $dimension,
        string $date,
        string $value,
        float $clicks,
        float $impressions,
        float $ctr,
        float $position,
    ): void {
        SeoSearchConsolePerformanceRow::query()->create([
            'seo_performance_sync_run_id' => $run->id,
            'seo_performance_connection_id' => $run->seo_performance_connection_id,
            'dimension_type' => $dimension,
            'search_type' => 'web',
            'data_date' => $date,
            'dimension_value' => $value,
            'dimension_hash' => hash('sha256', $value),
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $ctr,
            'position' => $position,
        ]);
    }
}
