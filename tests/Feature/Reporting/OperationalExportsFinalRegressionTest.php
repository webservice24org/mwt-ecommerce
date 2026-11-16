<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Operations\OperationalExportRegistryFactory;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalExportsFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_operational_exports_are_registered(): void
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

    public function test_all_registered_export_routes_exist(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertTrue(
                app(
                    'router',
                )->has(
                    $definition->routeName,
                ),
                sprintf(
                    'Missing operational export route [%s].',
                    $definition->routeName,
                ),
            );
        }
    }

    public function test_all_operational_exports_have_unique_routes(): void
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

    public function test_all_operational_exports_have_headers(): void
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
                sprintf(
                    'Duplicate CSV headers detected for [%s].',
                    $definition->type->value,
                ),
            );
        }
    }

    public function test_all_operational_exports_use_positive_row_limits(): void
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

    public function test_all_operational_exports_use_shared_row_limit(): void
    {
        config([
            'reporting.operational_exports.max_rows' => 5432,
        ]);

        $registry =
            app(
                OperationalExportRegistryFactory::class,
            )->make();

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertSame(
                5432,
                $definition->maxRows,
            );
        }
    }

    public function test_all_export_definitions_generate_csv_filenames(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $filename =
                $definition->filename(
                    '2026-11-16-153000',
                );

            $this->assertStringEndsWith(
                '.csv',
                $filename,
            );

            $this->assertStringContainsString(
                '2026-11-16-153000',
                $filename,
            );
        }
    }

    public function test_guests_cannot_access_any_operational_csv_export(): void
    {
        $registry =
            app(
                OperationalExportRegistry::class,
            );

        foreach (
            $registry->all() as $definition
        ) {
            $this
                ->get(
                    route(
                        $definition->routeName,
                    ),
                )
                ->assertRedirect();
        }
    }

    public function test_authenticated_admin_can_access_empty_operational_exports(): void
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
            $response =
                $this
                    ->actingAs(
                        $admin,
                        'admin',
                    )
                    ->get(
                        route(
                            $routeName,
                        ),
                    );

            $response
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'text/csv; charset=UTF-8',
                );
        }
    }

    public function test_all_operational_csv_exports_start_with_utf8_bom(): void
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
            $content =
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
                    ->streamedContent();

            $this->assertStringStartsWith(
                "\xEF\xBB\xBF",
                $content,
            );
        }
    }

    public function test_operational_orders_page_exposes_csv_export_action(): void
    {
        $this->assertPageContainsExport(
            path: resource_path(
                'js/Pages/Admin/Reports/OperationalOrders/Index.tsx',
            ),

            routeName: 'admin.reports.operations.orders.csv',
        );
    }

    public function test_cod_operations_page_exposes_csv_export_action(): void
    {
        $this->assertPageContainsExport(
            path: resource_path(
                'js/Pages/Admin/Reports/CodOperations/Index.tsx',
            ),

            routeName: 'admin.reports.operations.cod.csv',
        );
    }

    public function test_inventory_operations_page_exposes_csv_export_action(): void
    {
        $this->assertPageContainsExport(
            path: resource_path(
                'js/Pages/Admin/Reports/InventoryOperations/Index.tsx',
            ),

            routeName: 'admin.reports.operations.inventory.csv',
        );
    }

    public function test_action_queue_page_exposes_csv_export_action(): void
    {
        $this->assertPageContainsExport(
            path: resource_path(
                'js/Pages/Admin/Reports/ActionQueue/Index.tsx',
            ),

            routeName: 'admin.reports.operations.action-queue.csv',
        );
    }

    private function assertPageContainsExport(
        string $path,
        string $routeName,
    ): void {
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
