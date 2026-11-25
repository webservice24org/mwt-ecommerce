<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class GoogleAnalyticsPerformanceReportingContractTest extends TestCase
{
    public function test_o8_reporting_is_completed_snapshot_scoped_read_only_and_user_metric_safe(): void
    {
        $root = dirname(__DIR__, 4);
        $query = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Queries/GetGoogleAnalyticsPerformanceReportQuery.php',
        );
        $controller = file_get_contents(
            $root.'/app/Http/Controllers/Admin/Seo/GoogleAnalyticsPerformanceReportController.php',
        );
        $tsx = file_get_contents(
            $root.'/resources/js/Pages/Admin/Seo/Performance/GoogleAnalytics.tsx',
        );

        self::assertIsString($query);
        self::assertIsString($controller);
        self::assertIsString($tsx);

        self::assertStringContainsString(
            "->where('scope', SeoPerformanceSyncScope::LandingAnalytics->value)",
            $query,
        );
        self::assertStringContainsString(
            "->where('status', SeoPerformanceSyncStatus::Completed->value)",
            $query,
        );
        self::assertStringContainsString(
            'SeoPerformanceProvider::GoogleAnalytics->value',
            $query,
        );
        self::assertStringContainsString(
            "where('seo_performance_sync_run_id', \$runId)",
            $query,
        );
        self::assertStringContainsString("whereDate('data_date', '>=', \$dateFrom)", $query);
        self::assertStringContainsString("whereDate('data_date', '<=', \$dateTo)", $query);
        self::assertStringContainsString('engagement_rate', $query);
        self::assertStringContainsString('active_users_row_sum', $query);
        self::assertStringContainsString('not safely additive', $query);
        self::assertStringNotContainsString('Http::', $query);
        self::assertStringNotContainsString('dispatch(', $query);

        self::assertStringContainsString("'viewAny'", $controller);
        self::assertStringContainsString('Product::class', $controller);

        self::assertStringContainsString('router.get(', $tsx);
        self::assertStringNotContainsString('router.post(', $tsx);
        self::assertStringNotContainsString('router.patch(', $tsx);
        self::assertStringNotContainsString('router.delete(', $tsx);
        self::assertStringContainsString('Active users*', $tsx);
        self::assertStringContainsString('New users*', $tsx);
    }
}
