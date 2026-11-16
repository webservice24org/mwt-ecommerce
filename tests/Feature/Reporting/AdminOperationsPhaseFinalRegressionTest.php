<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminOperationsPhaseFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_phase_routes_are_registered(): void
    {
        $routes = [
            'admin.reports.operations.orders',
            'admin.reports.operations.orders.csv',

            'admin.reports.operations.cod',
            'admin.reports.operations.cod.csv',

            'admin.reports.operations.inventory',
            'admin.reports.operations.inventory.csv',

            'admin.reports.operations.action-queue',
            'admin.reports.operations.action-queue.csv',
        ];

        foreach (
            $routes as $routeName
        ) {
            $this->assertTrue(
                app('router')->has(
                    $routeName,
                ),
                sprintf(
                    'Missing Phase 10.3 route [%s].',
                    $routeName,
                ),
            );
        }
    }

    public function test_guests_cannot_access_any_phase_report(): void
    {
        $routes = [
            'admin.reports.operations.orders',
            'admin.reports.operations.orders.csv',

            'admin.reports.operations.cod',
            'admin.reports.operations.cod.csv',

            'admin.reports.operations.inventory',
            'admin.reports.operations.inventory.csv',

            'admin.reports.operations.action-queue',
            'admin.reports.operations.action-queue.csv',
        ];

        foreach (
            $routes as $routeName
        ) {
            $this
                ->get(
                    route(
                        $routeName,
                    ),
                )
                ->assertRedirect();
        }
    }

    public function test_admin_can_access_all_phase_report_pages(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $routes = [
            'admin.reports.operations.orders',
            'admin.reports.operations.cod',
            'admin.reports.operations.inventory',
            'admin.reports.operations.action-queue',
        ];

        foreach (
            $routes as $routeName
        ) {
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        $routeName,
                    ),
                )
                ->assertOk();
        }
    }

    public function test_admin_can_access_all_phase_exports(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $routes = [
            'admin.reports.operations.orders.csv',
            'admin.reports.operations.cod.csv',
            'admin.reports.operations.inventory.csv',
            'admin.reports.operations.action-queue.csv',
        ];

        foreach (
            $routes as $routeName
        ) {
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        $routeName,
                    ),
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'text/csv; charset=UTF-8',
                );
        }
    }

    public function test_operational_export_registry_contains_all_phase_exports(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        $this->assertCount(
            4,
            $registry->all(),
        );

        foreach (
            OperationalExportType::cases() as $type
        ) {
            $this->assertTrue(
                $registry->has(
                    $type,
                ),
            );
        }
    }

    public function test_operational_export_routes_match_registered_routes(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertTrue(
                app('router')->has(
                    $definition->routeName,
                ),
            );
        }
    }

    public function test_all_registered_exports_have_unique_routes(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        $routes =
            array_map(
                static fn (
                    $definition,
                ): string => $definition->routeName,
                $registry->all(),
            );

        $this->assertSame(
            count(
                $routes,
            ),
            count(
                array_unique(
                    $routes,
                ),
            ),
        );
    }

    public function test_all_registered_exports_have_unique_headers_per_export(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertNotEmpty(
                $definition->headers,
            );

            $this->assertSame(
                count(
                    $definition->headers,
                ),
                count(
                    array_unique(
                        $definition->headers,
                    ),
                ),
            );
        }
    }

    public function test_all_registered_exports_use_positive_row_limits(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertGreaterThan(
                0,
                $definition->maxRows,
            );
        }
    }

    public function test_all_phase_pages_have_export_actions(): void
    {
        $pages = [
            [
                resource_path(
                    'js/Pages/Admin/Reports/OperationalOrders/Index.tsx',
                ),
                'admin.reports.operations.orders.csv',
            ],

            [
                resource_path(
                    'js/Pages/Admin/Reports/CodOperations/Index.tsx',
                ),
                'admin.reports.operations.cod.csv',
            ],

            [
                resource_path(
                    'js/Pages/Admin/Reports/InventoryOperations/Index.tsx',
                ),
                'admin.reports.operations.inventory.csv',
            ],

            [
                resource_path(
                    'js/Pages/Admin/Reports/ActionQueue/Index.tsx',
                ),
                'admin.reports.operations.action-queue.csv',
            ],
        ];

        foreach (
            $pages as [
                $path,
                $routeName,
            ]
        ) {
            $source =
                file_get_contents(
                    $path,
                );

            $this->assertIsString(
                $source,
            );

            $this->assertStringContainsString(
                'Export CSV',
                $source,
            );

            $this->assertStringContainsString(
                $routeName,
                $source,
            );
        }
    }

    public function test_admin_sidebar_contains_all_operational_reports(): void
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
            'COD Operations',
            $source,
        );

        $this->assertStringContainsString(
            'Inventory Operations',
            $source,
        );

        $this->assertStringContainsString(
            'Action Queue',
            $source,
        );
    }

    public function test_all_phase_export_files_use_shared_export_infrastructure(): void
    {
        $controllers = [
            app_path(
                'Http/Controllers/Admin/Reports/OperationalOrdersCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/CodOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/InventoryOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/ActionQueueCsvExportController.php',
            ),
        ];

        foreach (
            $controllers as $path
        ) {
            $source =
                file_get_contents(
                    $path,
                );

            $this->assertIsString(
                $source,
            );

            $this->assertStringContainsString(
                'OperationalExportRegistry',
                $source,
            );

            $this->assertStringContainsString(
                'OperationalCsvFormatter',
                $source,
            );

            $this->assertStringContainsString(
                '$definition->headers',
                $source,
            );

            $this->assertStringContainsString(
                '$definition->maxRows',
                $source,
            );

            $this->assertStringContainsString(
                '$definition->filename()',
                $source,
            );
        }
    }

    public function test_phase_export_controllers_do_not_reintroduce_local_row_limit_constants(): void
    {
        $controllers = [
            app_path(
                'Http/Controllers/Admin/Reports/OperationalOrdersCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/CodOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/InventoryOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/ActionQueueCsvExportController.php',
            ),
        ];

        foreach (
            $controllers as $path
        ) {
            $source =
                file_get_contents(
                    $path,
                );

            $this->assertIsString(
                $source,
            );

            $this->assertStringNotContainsString(
                'MAX_EXPORT_ROWS',
                $source,
            );
        }
    }

    public function test_phase_export_controllers_do_not_reintroduce_local_csv_safety_helpers(): void
    {
        $controllers = [
            app_path(
                'Http/Controllers/Admin/Reports/OperationalOrdersCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/CodOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/InventoryOperationsCsvExportController.php',
            ),

            app_path(
                'Http/Controllers/Admin/Reports/ActionQueueCsvExportController.php',
            ),
        ];

        foreach (
            $controllers as $path
        ) {
            $source =
                file_get_contents(
                    $path,
                );

            $this->assertIsString(
                $source,
            );

            $this->assertStringNotContainsString(
                'function csvSafe',
                $source,
            );
        }
    }
}
