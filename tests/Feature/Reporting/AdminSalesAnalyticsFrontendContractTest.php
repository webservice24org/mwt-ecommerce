<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminSalesAnalyticsFrontendContractTest extends TestCase
{
    public function test_sales_analytics_page_contains_kpis_and_chart_contracts(): void
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

        $this->assertStringContainsString(
            'Sales Analytics',
            $source,
        );

        $this->assertStringContainsString(
            'Total Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'Paid Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'Average Order Value',
            $source,
        );

        $this->assertStringContainsString(
            'Delivery Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'charts.revenue_trend',
            $source,
        );

        $this->assertStringContainsString(
            'charts.orders_trend',
            $source,
        );

        $this->assertStringContainsString(
            'charts.status_revenue',
            $source,
        );
    }

    public function test_shared_chart_component_uses_responsive_charts(): void
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
            'ResponsiveContainer',
            $source,
        );

        $this->assertStringContainsString(
            'LineChart',
            $source,
        );

        $this->assertStringContainsString(
            'BarChart',
            $source,
        );

        $this->assertStringContainsString(
            'No chart data',
            $source,
        );
    }
}
