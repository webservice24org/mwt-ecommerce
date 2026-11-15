<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminProductAnalyticsFrontendContractTest extends TestCase
{
    public function test_product_analytics_page_contains_dashboard_contracts(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'Product Analytics',
            $source,
        );

        $this->assertStringContainsString(
            'Product / SKU',
            $source,
        );

        $this->assertStringContainsString(
            'Apply filters',
            $source,
        );

        $this->assertStringContainsString(
            'Reset',
            $source,
        );

        $this->assertStringContainsString(
            "'admin.reports.products.analytics'",
            $source,
        );
    }

    public function test_product_analytics_page_contains_all_kpi_contracts(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'Product Groups',
            $source,
        );

        $this->assertStringContainsString(
            'Quantity Sold',
            $source,
        );

        $this->assertStringContainsString(
            'Orders',
            $source,
        );

        $this->assertStringContainsString(
            'Gross Sales',
            $source,
        );

        $this->assertStringContainsString(
            'Avg. Revenue / Order',
            $source,
        );

        $this->assertStringContainsString(
            'Avg. Revenue / Item',
            $source,
        );

        $this->assertStringContainsString(
            'Current Product Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'Historical Product Revenue',
            $source,
        );
    }

    public function test_product_analytics_page_contains_all_chart_contracts(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'charts.sales_trend',
            $source,
        );

        $this->assertStringContainsString(
            'charts.top_products_by_revenue',
            $source,
        );

        $this->assertStringContainsString(
            'charts.top_products_by_quantity',
            $source,
        );

        $this->assertStringContainsString(
            'charts.current_vs_historical_revenue',
            $source,
        );

        $this->assertStringContainsString(
            'charts.sku_performance',
            $source,
        );

        $this->assertStringContainsString(
            'charts.revenue_share_by_product',
            $source,
        );

        $this->assertStringContainsString(
            '<AnalyticsChart',
            $source,
        );
    }

    public function test_product_analytics_page_contains_empty_state_contract(): void
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
            'No product analytics',
            $source,
        );

        $this->assertStringContainsString(
            'Reset analytics filters',
            $source,
        );
    }

    public function test_product_analytics_page_uses_integer_money_formatter(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'value / 100',
            $source,
        );

        $this->assertStringContainsString(
            'currency:',
            $source,
        );

        $this->assertStringContainsString(
            "'BDT'",
            $source,
        );

        $this->assertStringContainsString(
            'formatMoney(',
            $source,
        );
    }

    public function test_product_analytics_page_contains_product_and_status_filters(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'Product / SKU',
            $source,
        );

        $this->assertStringContainsString(
            'Order Status',
            $source,
        );

        $this->assertStringContainsString(
            'Payment Status',
            $source,
        );

        $this->assertStringContainsString(
            'Product name or SKU',
            $source,
        );

        $this->assertStringContainsString(
            'payment_status:',
            $source,
        );

        $this->assertStringContainsString(
            'status:',
            $source,
        );
    }

    public function test_product_analytics_page_uses_shared_reporting_components(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            '@/Components/Admin/Reports/AnalyticsChart',
            $source,
        );

        $this->assertStringContainsString(
            '@/Components/Admin/Reports/ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            '@/Layouts/Admin/AdminLayout',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            '<AdminLayout',
            $source,
        );
    }

    public function test_product_analytics_kpi_layout_is_responsive(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            'sm:grid-cols-2',
            $source,
        );

        $this->assertStringContainsString(
            'lg:grid-cols-3',
            $source,
        );

        $this->assertStringContainsString(
            '2xl:grid-cols-4',
            $source,
        );

        $this->assertStringContainsString(
            'min-w-0',
            $source,
        );

        $this->assertStringContainsString(
            'break-words',
            $source,
        );
    }

    public function test_product_analytics_page_preserves_server_authoritative_summary_fields(): void
    {
        $source =
            $this->pageSource();

        $this->assertStringContainsString(
            '.product_groups_count',
            $source,
        );

        $this->assertStringContainsString(
            '.quantity_sold',
            $source,
        );

        $this->assertStringContainsString(
            '.orders_count',
            $source,
        );

        $this->assertStringContainsString(
            '.gross_sales',
            $source,
        );

        $this->assertStringContainsString(
            '.average_revenue_per_order',
            $source,
        );

        $this->assertStringContainsString(
            '.average_revenue_per_item',
            $source,
        );

        $this->assertStringContainsString(
            '.current_product_revenue',
            $source,
        );

        $this->assertStringContainsString(
            '.historical_product_revenue',
            $source,
        );
    }

    private function pageSource(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/ProductAnalytics/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }
}
