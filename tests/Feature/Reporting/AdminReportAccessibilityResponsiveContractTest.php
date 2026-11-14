<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminReportAccessibilityResponsiveContractTest extends TestCase
{
    public function test_sales_report_has_accessible_empty_state(): void
    {
        $source =
            $this->source(
                'Sales/Index.tsx',
            );

        $this->assertStringContainsString(
            'role="status"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );

        $this->assertStringContainsString(
            'Reset report filters',
            $source,
        );
    }

    public function test_product_report_has_accessible_empty_state(): void
    {
        $source =
            $this->source(
                'Products/Index.tsx',
            );

        $this->assertStringContainsString(
            'role="status"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );

        $this->assertStringContainsString(
            'Reset report filters',
            $source,
        );
    }

    public function test_sales_report_table_and_pagination_have_accessibility_semantics(): void
    {
        $source =
            $this->source(
                'Sales/Index.tsx',
            );

        $this->assertStringContainsString(
            'aria-label="Sales report orders"',
            $source,
        );

        $this->assertStringContainsString(
            'scope="col"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Report pagination"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-current={',
            $source,
        );
    }

    public function test_product_report_table_and_pagination_have_accessibility_semantics(): void
    {
        $source =
            $this->source(
                'Products/Index.tsx',
            );

        $this->assertStringContainsString(
            'aria-label="Product performance report"',
            $source,
        );

        $this->assertStringContainsString(
            'scope="col"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Report pagination"',
            $source,
        );
    }

    public function test_both_reports_preserve_desktop_and_mobile_result_views(): void
    {
        foreach (
            [
                'Sales/Index.tsx',
                'Products/Index.tsx',
            ] as $file
        ) {
            $source =
                $this->source(
                    $file,
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

    public function test_shared_report_header_announces_export_errors(): void
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
            'role="alert"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="assertive"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-busy',
            $source,
        );
    }

    private function source(
        string $file,
    ): string {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/'.
                    $file,
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }
}
