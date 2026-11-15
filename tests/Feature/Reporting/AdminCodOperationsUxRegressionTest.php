<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminCodOperationsUxRegressionTest extends TestCase
{
    public function test_cod_operations_page_contains_workflow_polish_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/CodOperations/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Quick workflows',
            $source,
        );

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'Delivered + Due',
            $source,
        );

        $this->assertStringContainsString(
            'Delivered + Unpaid',
            $source,
        );

        $this->assertStringContainsString(
            'Clear search',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.cod',
            $source,
        );

        $this->assertStringContainsString(
            'preserveState',
            $source,
        );

        $this->assertStringContainsString(
            'preserveScroll',
            $source,
        );

        $this->assertStringContainsString(
            'collection_risk.requires_follow_up',
            $source,
        );

        $this->assertStringContainsString(
            'collection_risk.severity',
            $source,
        );

        $this->assertStringContainsString(
            'admin.orders.show',
            $source,
        );

        $this->assertStringContainsString(
            'MobileOrderCard',
            $source,
        );
        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.cod.csv',
            $source,
        );
    }

    public function test_cod_operations_sidebar_navigation_contract_is_present(): void
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
            'COD Operations',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.cod',
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/cod',
            $source,
        );
    }
}
