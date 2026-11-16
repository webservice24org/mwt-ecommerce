<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminInventoryOperationsUxRegressionTest extends TestCase
{
    public function test_inventory_operations_page_contains_workflow_polish_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/InventoryOperations/Index.tsx',
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
            'Needs Attention',
            $source,
        );

        $this->assertStringContainsString(
            'Negative Stock',
            $source,
        );

        $this->assertStringContainsString(
            'Out of Stock',
            $source,
        );

        $this->assertStringContainsString(
            'Low Stock',
            $source,
        );

        $this->assertStringContainsString(
            'Healthy',
            $source,
        );

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'Clear search',
            $source,
        );

        $this->assertStringContainsString(
            'Clear all',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.inventory',
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
            'alert.requires_attention',
            $source,
        );

        $this->assertStringContainsString(
            'alert.severity',
            $source,
        );

        $this->assertStringContainsString(
            'admin.products.edit',
            $source,
        );

        $this->assertStringContainsString(
            'MobileProductCard',
            $source,
        );

        $this->assertStringContainsString(
            'Review Product',
            $source,
        );

        $this->assertStringContainsString(
            "'low_stock'",
            $source,
        );

        $this->assertStringContainsString(
            "'out_of_stock'",
            $source,
        );

        $this->assertStringContainsString(
            "'negative_stock'",
            $source,
        );
        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.inventory.csv',
            $source,
        );
    }

    public function test_inventory_operations_sidebar_navigation_contract_is_present(): void
    {
        $sidebar =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/AdminSidebar.tsx',
                ),
            );

        $this->assertIsString(
            $sidebar,
        );

        $this->assertStringContainsString(
            'Inventory Operations',
            $sidebar,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.inventory',
            $sidebar,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/inventory',
            $sidebar,
        );
    }
}
