<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminReportExportFrontendContractTest extends TestCase
{
    private function reportHeaderSource(): string
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

        return $source;
    }

    public function test_sales_report_has_filter_preserving_csv_download(): void
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
            "'admin.reports.sales.export'",
            $source,
        );

        $this->assertStringContainsString(
            'query,',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl={',
            $source,
        );
    }

    public function test_product_report_has_filter_preserving_csv_download(): void
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
            "'admin.reports.products.export'",
            $source,
        );

        $this->assertStringContainsString(
            'query,',
            $source,
        );

        $this->assertStringContainsString(
            '<ReportHeader',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl={',
            $source,
        );
    }

    public function test_shared_report_header_owns_csv_download_action(): void
    {
        $source =
            $this->reportHeaderSource();

        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            '<button',
            $source,
        );

        $this->assertStringContainsString(
            'onClick={',
            $source,
        );

        $this->assertStringContainsString(
            'downloadExport',
            $source,
        );

        $this->assertStringContainsString(
            'fetch(',
            $source,
        );

        $this->assertStringContainsString(
            'exportUrl',
            $source,
        );
    }
}
