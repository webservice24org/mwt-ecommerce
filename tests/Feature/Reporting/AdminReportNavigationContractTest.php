<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminReportNavigationContractTest extends TestCase
{
    public function test_shared_report_navigation_contains_both_reports(): void
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
            "'admin.reports.sales.index'",
            $source,
        );

        $this->assertStringContainsString(
            "'admin.reports.products.index'",
            $source,
        );

        $this->assertStringContainsString(
            'Sales Report',
            $source,
        );

        $this->assertStringContainsString(
            'Product Performance',
            $source,
        );
    }

    public function test_report_navigation_exposes_active_page_semantics(): void
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
            'aria-current',
            $source,
        );

        $this->assertStringContainsString(
            "'page'",
            $source,
        );

        $this->assertStringContainsString(
            'isActive',
            $source,
        );
    }

    public function test_report_header_contains_breadcrumb_navigation(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/ReportHeader.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Breadcrumb"',
            $source,
        );

        $this->assertStringContainsString(
            "'admin.dashboard'",
            $source,
        );

        $this->assertStringContainsString(
            'Reports',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportNavigation',
            $source,
        );
    }

    public function test_sales_report_declares_sales_as_active_report(): void
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
            'activeReport="sales"',
            $source,
        );

        $this->assertStringContainsString(
            'title="Sales Report"',
            $source,
        );
    }

    public function test_product_report_declares_products_as_active_report(): void
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

        $this->assertStringContainsString(
            'activeReport="products"',
            $source,
        );

        $this->assertStringContainsString(
            'title="Product Performance"',
            $source,
        );
    }

    public function test_sidebar_contains_report_routes_and_prefix_matches(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/AdminSidebar.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/sales',
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/products',
            $source,
        );

        $this->assertStringContainsString(
            'url.startsWith',
            $source,
        );
    }
}
