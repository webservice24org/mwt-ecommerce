<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminSalesReportUxRegressionTest extends TestCase
{
    public function test_admin_sidebar_exposes_sales_report_navigation(): void
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
            "'admin.reports.sales.index'",
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/sales',
            $source,
        );

        $this->assertStringContainsString(
            'Sales Report',
            $source,
        );
    }

    public function test_sales_report_contains_active_filter_management(): void
    {
        $source =
            $this->salesReportSource();

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'FilterChip',
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

    public function test_sales_report_pagination_preserves_query_contract(): void
    {
        $source =
            $this->salesReportSource();

        $this->assertStringContainsString(
            '...query',
            $source,
        );

        $this->assertStringContainsString(
            'visitReport',
            $source,
        );

        $this->assertStringContainsString(
            'Math.min(',
            $source,
        );

        $this->assertStringContainsString(
            'Math.max(',
            $source,
        );

        $this->assertStringContainsString(
            'lastPage',
            $source,
        );
    }

    public function test_sales_report_pagination_exposes_first_previous_next_and_last_actions(): void
    {
        $source =
            $this->salesReportSource();

        $this->assertStringContainsString(
            'First',
            $source,
        );

        $this->assertStringContainsString(
            'Previous',
            $source,
        );

        $this->assertStringContainsString(
            'Next',
            $source,
        );

        $this->assertStringContainsString(
            'Last',
            $source,
        );
    }

    public function test_sales_report_filter_submission_resets_pagination(): void
    {
        $source =
            $this->salesReportSource();

        $submitStart =
            strpos(
                $source,
                'const submitFilters',
            );

        $goToPageStart =
            strpos(
                $source,
                'const goToPage',
            );

        $this->assertNotFalse(
            $submitStart,
        );

        $this->assertNotFalse(
            $goToPageStart,
        );

        $submitSource =
            substr(
                $source,
                $submitStart,
                $goToPageStart -
                $submitStart,
            );

        $this->assertDoesNotMatchRegularExpression(
            '/\bpage\s*[:,]/',
            $submitSource,
        );
    }

    private function salesReportSource(): string
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

        return $source;
    }
}
