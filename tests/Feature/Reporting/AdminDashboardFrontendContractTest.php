<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminDashboardFrontendContractTest extends TestCase
{
    public function test_dashboard_consumes_complete_reporting_payload(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'trend:',
            $source,
        );

        $this->assertStringContainsString(
            'breakdowns:',
            $source,
        );

        $this->assertStringContainsString(
            'recentOrders:',
            $source,
        );

        $this->assertStringContainsString(
            'operationalSummary:',
            $source,
        );

        $this->assertStringContainsString(
            'Sales trend',
            $source,
        );

        $this->assertStringContainsString(
            'Payment breakdown',
            $source,
        );

        $this->assertStringContainsString(
            'Fulfilment breakdown',
            $source,
        );

        $this->assertStringContainsString(
            'Recent orders',
            $source,
        );

        $this->assertStringContainsString(
            'Operational',
            $source,
        );
    }

    public function test_dashboard_money_formatter_converts_minor_units(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
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

    public function test_recent_order_links_use_existing_admin_order_route(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            "'admin.orders.show'",
            $source,
        );

        $this->assertStringContainsString(
            "'admin.orders.index'",
            $source,
        );
    }

    public function test_dashboard_consumes_product_and_inventory_reporting_payload(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'topProducts:',
            $source,
        );

        $this->assertStringContainsString(
            'inventorySummary:',
            $source,
        );

        $this->assertStringContainsString(
            'inventoryAlerts:',
            $source,
        );

        $this->assertStringContainsString(
            'Top products',
            $source,
        );

        $this->assertStringContainsString(
            'Inventory overview',
            $source,
        );

        $this->assertStringContainsString(
            'Inventory alerts',
            $source,
        );

        $this->assertStringContainsString(
            'Out of stock',
            $source,
        );

        $this->assertStringContainsString(
            'Low stock',
            $source,
        );
    }

    public function test_dashboard_frontend_handles_historical_deleted_products(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'product.product_id ===',
            $source,
        );

        $this->assertStringContainsString(
            'Historical product',
            $source,
        );
    }
}
