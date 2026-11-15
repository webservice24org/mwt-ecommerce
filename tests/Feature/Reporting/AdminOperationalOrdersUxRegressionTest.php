<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminOperationalOrdersUxRegressionTest extends TestCase
{
    public function test_operational_orders_page_contains_workflow_ux_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/OperationalOrders/Index.tsx',
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
            'admin.reports.operations.orders',
            $source,
        );

        $this->assertStringContainsString(
            'onPageChange',
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
            'attention.requires_attention',
            $source,
        );

        $this->assertStringContainsString(
            'attention.severity',
            $source,
        );

        $this->assertStringContainsString(
            'admin.orders.show',
            $source,
        );
    }

    public function test_admin_sidebar_contains_operational_orders_report_navigation(): void
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
            'Operational Orders',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.orders',
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/orders',
            $source,
        );
    }
}
