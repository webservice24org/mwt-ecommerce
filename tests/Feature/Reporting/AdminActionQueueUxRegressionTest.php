<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use Tests\TestCase;

final class AdminActionQueueUxRegressionTest extends TestCase
{
    public function test_action_queue_page_contains_workflow_polish_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/ActionQueue/Index.tsx',
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
            'Needs Action',
            $source,
        );

        $this->assertStringContainsString(
            'Critical',
            $source,
        );

        $this->assertStringContainsString(
            'High Priority',
            $source,
        );

        $this->assertStringContainsString(
            'Orders',
            $source,
        );

        $this->assertStringContainsString(
            'COD Collection',
            $source,
        );

        $this->assertStringContainsString(
            'Inventory',
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
            'MobileQueueCard',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.action-queue',
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
            'item.action_url',
            $source,
        );

        $this->assertStringContainsString(
            'item.action_label',
            $source,
        );
        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.action-queue.csv',
            $source,
        );
    }

    public function test_action_queue_sidebar_navigation_contract_is_present(): void
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
            'Action Queue',
            $sidebar,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.action-queue',
            $sidebar,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/action-queue',
            $sidebar,
        );
    }
}
