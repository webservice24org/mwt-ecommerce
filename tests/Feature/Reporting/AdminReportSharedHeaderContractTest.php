<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminReportSharedHeaderContractTest extends TestCase
{
    public function test_shared_report_header_component_exists(): void
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
            'ReportHeaderProps',
            $source,
        );

        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            '<button',
            $source,
        );

        $this->assertStringContainsString(
            'downloadExport',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl',
            $source,
        );
    }

    public function test_sales_report_uses_shared_report_header(): void
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
            '@/Components/Admin/Reports/ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            'title="Sales Report"',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl={',
            $source,
        );
    }

    public function test_product_report_uses_shared_report_header(): void
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
            '@/Components/Admin/Reports/ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            'title="Product Performance"',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl={',
            $source,
        );
    }

    public function test_export_action_is_not_duplicated_inside_sales_filter_form(): void
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

        $this->assertSame(
            1,
            substr_count(
                $source,
                '<ReportHeader',
            ),
        );
    }

    public function test_export_action_is_not_duplicated_inside_product_filter_form(): void
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

        $this->assertSame(
            1,
            substr_count(
                $source,
                '<ReportHeader',
            ),
        );
    }
}
