<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminSalesReportFrontendContractTest extends TestCase
{
    public function test_sales_report_page_consumes_complete_report_contract(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

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

    public function test_sales_report_page_contains_filter_controls(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Date range',
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
            'Search',
            $source,
        );

        $this->assertStringContainsString(
            'Rows per page',
            $source,
        );
    }

    public function test_sales_report_preserves_query_when_paginating(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '...query',
            $source,
        );

        $this->assertStringContainsString(
            'page,',
            $source,
        );
    }

    public function test_sales_report_links_to_order_detail_page(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "'admin.orders.show'",
            $source,
        );
    }

    public function test_sales_report_formats_integer_money_as_minor_units(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'value / 100',
            $source,
        );
    }

    public function test_sales_report_has_responsive_desktop_and_mobile_order_views(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/Sales/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'hidden overflow-x-auto lg:block',
            $source,
        );

        $this->assertStringContainsString(
            'lg:hidden',
            $source,
        );
    }
}
