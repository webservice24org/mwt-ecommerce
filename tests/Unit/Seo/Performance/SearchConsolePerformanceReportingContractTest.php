<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class SearchConsolePerformanceReportingContractTest extends TestCase
{
    public function test_o5_reporting_is_snapshot_scoped_read_only_and_dimension_safe(): void
    {
        $root = dirname(__DIR__, 4);
        $query = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Queries/GetSearchConsolePerformanceReportQuery.php',
        );
        $controller = file_get_contents(
            $root.'/app/Http/Controllers/Admin/Seo/SearchConsolePerformanceReportController.php',
        );
        $tsx = file_get_contents(
            $root.'/resources/js/Pages/Admin/Seo/Performance/SearchConsole.tsx',
        );

        self::assertIsString($query);
        self::assertIsString($controller);
        self::assertIsString($tsx);

        self::assertStringContainsString(
            "->where('status', SeoPerformanceSyncStatus::Completed->value)",
            $query,
        );
        self::assertStringContainsString(
            'SearchConsolePerformanceDimension::LandingPage',
            $query,
        );
        self::assertStringContainsString(
            'SearchConsolePerformanceDimension::Query',
            $query,
        );
        self::assertStringContainsString(
            "where('seo_performance_sync_run_id', \$runId)",
            $query,
        );
        self::assertStringContainsString(
            'SUM(position * impressions)',
            $query,
        );
        self::assertStringNotContainsString('google_analytics', $query);
        self::assertStringNotContainsString('Http::', $query);
        self::assertStringNotContainsString('dispatch(', $query);

        self::assertStringContainsString("'viewAny'", $controller);
        self::assertStringContainsString('Product::class', $controller);

        self::assertStringContainsString('router.get(', $tsx);
        self::assertStringNotContainsString('router.post(', $tsx);
        self::assertStringNotContainsString('router.patch(', $tsx);
        self::assertStringNotContainsString('router.delete(', $tsx);
    }
}
