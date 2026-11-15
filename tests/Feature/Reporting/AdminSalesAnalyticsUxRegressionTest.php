<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminSalesAnalyticsUxRegressionTest extends TestCase
{
    public function test_sales_analytics_declares_its_own_active_report_state(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'activeReport="sales-analytics"',
            $source,
        );
    }

    public function test_sales_analytics_has_active_filter_summary(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'FilterChip',
            $source,
        );
    }

    public function test_sales_analytics_has_accessible_empty_state(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'role="status"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );

        $this->assertStringContainsString(
            'Reset analytics filters',
            $source,
        );
    }

    public function test_chart_component_supports_responsive_horizontal_bars(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/AnalyticsChart.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'useHorizontalBars',
            $source,
        );

        $this->assertStringContainsString(
            'layout={',
            $source,
        );

        $this->assertStringContainsString(
            'type="category"',
            $source,
        );

        $this->assertStringContainsString(
            'type="number"',
            $source,
        );
    }

    public function test_report_navigation_contains_sales_analytics(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/ReportNavigation.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "'sales-analytics'",
            $source,
        );

        $this->assertStringContainsString(
            'Sales Analytics',
            $source,
        );

        $this->assertStringContainsString(
            "'admin.reports.sales.analytics'",
            $source,
        );
    }

    private function pageSource(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/SalesAnalytics/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }
}
