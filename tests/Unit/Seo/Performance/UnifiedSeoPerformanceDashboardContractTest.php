<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class UnifiedSeoPerformanceDashboardContractTest extends TestCase
{
    public function test_o9_is_read_only_snapshot_scoped_bounded_and_avoids_false_attribution(): void
    {
        $root = dirname(__DIR__, 4);

        $query = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Queries/GetUnifiedSeoPerformanceDashboardQuery.php',
        );
        $controller = file_get_contents(
            $root.'/app/Http/Controllers/Admin/Seo/UnifiedSeoPerformanceDashboardController.php',
        );
        $tsx = file_get_contents(
            $root.'/resources/js/Pages/Admin/Seo/Performance/Overview.tsx',
        );

        self::assertIsString($query);
        self::assertIsString($controller);
        self::assertIsString($tsx);

        self::assertStringContainsString('SeoPerformanceProvider::SearchConsole', $query);
        self::assertStringContainsString('SeoPerformanceProvider::GoogleAnalytics', $query);
        self::assertStringContainsString('SeoPerformanceSyncStatus::Completed', $query);
        self::assertStringContainsString('PAGE_CANDIDATES_PER_SOURCE = 200', $query);
        self::assertStringContainsString("whereDate('data_date', '>=', \$dateFrom)", $query);
        self::assertStringContainsString("whereDate('data_date', '<=', \$dateTo)", $query);
        self::assertStringContainsString('normalizeLandingPage', $query);
        self::assertStringContainsString('does not attribute GA4 sessions', $query);
        self::assertStringNotContainsString('Http::', $query);
        self::assertStringNotContainsString('dispatch(', $query);

        self::assertStringContainsString("'viewAny'", $controller);
        self::assertStringContainsString('Product::class', $controller);

        self::assertStringContainsString('router.get(', $tsx);
        self::assertStringNotContainsString('router.post(', $tsx);
        self::assertStringNotContainsString('router.patch(', $tsx);
        self::assertStringNotContainsString('router.delete(', $tsx);
        self::assertStringContainsString('not as an attribution funnel', $tsx);
        self::assertStringContainsString('no causal attribution', $tsx);
    }
}
