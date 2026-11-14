<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminProductPerformanceFrontendContractTest extends TestCase
{
    private function source(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Products/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }

    public function test_product_report_consumes_complete_inertia_contract(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'filters:',
            $source,
        );

        $this->assertStringContainsString(
            'query:',
            $source,
        );

        $this->assertStringContainsString(
            'options:',
            $source,
        );

        $this->assertStringContainsString(
            'report:',
            $source,
        );
    }

    public function test_product_report_contains_expected_filters(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'Date range',
            $source,
        );

        $this->assertStringContainsString(
            'Product name or SKU...',
            $source,
        );

        $this->assertStringContainsString(
            'Order status',
            $source,
        );

        $this->assertStringContainsString(
            'Payment status',
            $source,
        );

        $this->assertStringContainsString(
            'Rows per page',
            $source,
        );
    }

    public function test_product_report_formats_money_from_minor_units(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'value / 100',
            $source,
        );
    }

    public function test_product_report_preserves_filters_during_pagination(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            '...query',
            $source,
        );

        $this->assertStringContainsString(
            'page,',
            $source,
        );

        $this->assertStringContainsString(
            'visitReport',
            $source,
        );
    }

    public function test_product_report_distinguishes_current_and_historical_products(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'Historical',
            $source,
        );

        $this->assertStringContainsString(
            'Current',
            $source,
        );

        $this->assertStringContainsString(
            'row.product_id ===',
            $source,
        );
    }

    public function test_only_current_products_receive_catalog_drill_down_link(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            "'admin.products.edit'",
            $source,
        );

        $this->assertStringContainsString(
            'row.product_id !==',
            $source,
        );

        $this->assertMatchesRegularExpression(
            '/Historical\s+only/',
            $source,
        );
    }

    public function test_product_report_has_desktop_and_mobile_result_views(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'hidden overflow-x-auto lg:block',
            $source,
        );

        $this->assertStringContainsString(
            'lg:hidden',
            $source,
        );
    }

    public function test_product_report_exposes_active_filter_clear_actions(): void
    {
        $source =
            $this->source();

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'clearStatus',
            $source,
        );

        $this->assertStringContainsString(
            'clearPaymentStatus',
            $source,
        );

        $this->assertStringContainsString(
            'clearSearch',
            $source,
        );

        $this->assertStringContainsString(
            'Clear all',
            $source,
        );
    }
}
