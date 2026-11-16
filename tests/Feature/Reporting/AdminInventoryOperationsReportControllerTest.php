<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminInventoryOperationsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory_operations_report(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_inventory_operations_report(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertOk();
    }

    public function test_report_uses_expected_inertia_component_and_props(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Reports/InventoryOperations/Index',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'summary',
                    )
                    ->has(
                        'rows',
                    )
                    ->has(
                        'pagination',
                    )
                    ->has(
                        'options.statuses',
                    )
                    ->has(
                        'options.stock_states',
                    ),
            );
    }

    public function test_summary_and_rows_are_exposed(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Healthy Inventory Product',

            slug: 'healthy-inventory-product',

            sku: 'INV-HEALTHY',

            trackInventory: true,

            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Low Inventory Product',

            slug: 'low-inventory-product',

            sku: 'INV-LOW',

            trackInventory: true,

            stockQuantity: 4,
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.inventory',
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.products_count',
                        2,
                    )
                    ->where(
                        'summary.tracked_products_count',
                        2,
                    )
                    ->where(
                        'summary.in_stock_count',
                        1,
                    )
                    ->where(
                        'summary.low_stock_count',
                        1,
                    )
                    ->where(
                        'summary.out_of_stock_count',
                        0,
                    )
                    ->has(
                        'rows',
                        2,
                    ),
            );
    }

    public function test_search_filter_is_preserved_and_applied(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Searchable Inventory Product',

            slug: 'searchable-inventory-product',

            sku: 'INV-SEARCH-001',

            trackInventory: true,

            stockQuantity: 10,
        );

        $this->createProduct(
            name: 'Different Product',

            slug: 'different-product',

            sku: 'INV-OTHER-001',

            trackInventory: true,

            stockQuantity: 10,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'search' => 'INV-SEARCH-001',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.search',
                        'INV-SEARCH-001',
                    )
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.name',
                        'Searchable Inventory Product',
                    )
                    ->where(
                        'rows.0.sku',
                        'INV-SEARCH-001',
                    ),
            );
    }

    public function test_stock_state_filter_is_preserved_and_applied(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Low Stock Product',

            slug: 'low-stock-product',

            sku: 'INV-LOW',

            trackInventory: true,

            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Healthy Product',

            slug: 'healthy-product',

            sku: 'INV-HEALTHY',

            trackInventory: true,

            stockQuantity: 20,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'stock_state' => [
                            'low_stock',
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.stock_states',
                        [
                            'low_stock',
                        ],
                    )
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.name',
                        'Low Stock Product',
                    )
                    ->where(
                        'rows.0.stock_state',
                        'low_stock',
                    ),
            );
    }

    public function test_negative_stock_filter_returns_negative_stock_product(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Negative Stock Product',

            slug: 'negative-stock-product',

            sku: 'INV-NEGATIVE',

            trackInventory: true,

            stockQuantity: -3,
        );

        $this->createProduct(
            name: 'Zero Stock Product',

            slug: 'zero-stock-product',

            sku: 'INV-ZERO',

            trackInventory: true,

            stockQuantity: 0,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'stock_state' => [
                            'negative_stock',
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.stock_states',
                        [
                            'negative_stock',
                        ],
                    )
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->where(
                        'summary.negative_stock_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.name',
                        'Negative Stock Product',
                    )
                    ->where(
                        'rows.0.stock_state',
                        'negative_stock',
                    )
                    ->where(
                        'rows.0.alert.state',
                        'negative_stock',
                    )
                    ->where(
                        'rows.0.alert.severity',
                        'critical',
                    )
                    ->where(
                        'rows.0.alert.requires_attention',
                        true,
                    ),
            );
    }

    public function test_product_status_filter_is_preserved_and_applied(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $statuses =
            ProductStatus::cases();

        $this->assertNotEmpty(
            $statuses,
        );

        $selectedStatus =
            $statuses[0];

        Product::factory()
            ->create([
                'name' => 'Status Filter Product',

                'slug' => 'status-filter-product',

                'sku' => 'INV-STATUS',

                'status' => $selectedStatus,

                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'status' => [
                            $selectedStatus->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.statuses',
                        [
                            $selectedStatus->value,
                        ],
                    )
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.name',
                        'Status Filter Product',
                    )
                    ->where(
                        'rows.0.status',
                        $selectedStatus->value,
                    ),
            );
    }

    public function test_multiple_filters_are_combined(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Target Low Product',

            slug: 'target-low-product',

            sku: 'INV-TARGET',

            trackInventory: true,

            stockQuantity: 3,
        );

        $this->createProduct(
            name: 'Other Low Product',

            slug: 'other-low-product',

            sku: 'INV-OTHER',

            trackInventory: true,

            stockQuantity: 3,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'search' => 'INV-TARGET',

                        'stock_state' => [
                            'low_stock',
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.search',
                        'INV-TARGET',
                    )
                    ->where(
                        'filters.stock_states',
                        [
                            'low_stock',
                        ],
                    )
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.sku',
                        'INV-TARGET',
                    ),
            );
    }

    public function test_per_page_filter_is_preserved(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'per_page' => 50,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.per_page',
                        50,
                    )
                    ->where(
                        'pagination.per_page',
                        50,
                    ),
            );
    }

    public function test_pagination_uses_requested_page(): void
    {
        $admin =
            Admin::factory()
                ->create();

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $this->createProduct(
                name: 'Pagination Product '.$index,

                slug: 'pagination-product-'.$index,

                sku: 'INV-PAGE-'.$index,

                trackInventory: true,

                stockQuantity: 10,
            );
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'per_page' => 10,

                        'page' => 2,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.current_page',
                        2,
                    )
                    ->where(
                        'pagination.per_page',
                        10,
                    )
                    ->where(
                        'pagination.total',
                        12,
                    )
                    ->has(
                        'rows',
                        2,
                    ),
            );
    }

    public function test_invalid_stock_state_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'stock_state' => [
                            'invalid_stock_state',
                        ],
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'stock_state.0',
            );
    }

    public function test_invalid_product_status_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'status' => [
                            'invalid_product_status',
                        ],
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'status.0',
            );
    }

    public function test_invalid_per_page_value_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    [
                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'per_page',
            );
    }

    public function test_negative_stock_alert_summary_is_exposed(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Negative Product',

            slug: 'negative-product',

            sku: 'INV-NEG',

            trackInventory: true,

            stockQuantity: -2,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.attention_required_count',
                        1,
                    )
                    ->where(
                        'summary.critical_alert_count',
                        1,
                    )
                    ->where(
                        'summary.high_alert_count',
                        0,
                    )
                    ->where(
                        'summary.negative_stock_count',
                        1,
                    )
                    ->where(
                        'rows.0.alert.state',
                        'negative_stock',
                    )
                    ->where(
                        'rows.0.alert.priority',
                        90,
                    ),
            );
    }

    public function test_options_include_stock_states(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->has(
                        'options.stock_states',
                        4,
                    )
                    ->where(
                        'options.stock_states.0.value',
                        'in_stock',
                    )
                    ->where(
                        'options.stock_states.1.value',
                        'low_stock',
                    )
                    ->where(
                        'options.stock_states.2.value',
                        'out_of_stock',
                    )
                    ->where(
                        'options.stock_states.3.value',
                        'negative_stock',
                    ),
            );
    }

    public function test_options_include_all_product_statuses(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->has(
                        'options.statuses',
                        count(
                            ProductStatus::cases(),
                        ),
                    ),
            );
    }

    private function createProduct(
        string $name,
        string $slug,
        string $sku,
        bool $trackInventory,
        int $stockQuantity,
    ): Product {
        return Product::factory()
            ->create([
                'name' => $name,

                'slug' => $slug,

                'sku' => $sku,

                /*
                 * Let the project's ProductFactory provide
                 * the valid ProductStatus enum value.
                 */
                'track_inventory' => $trackInventory,

                'stock_quantity' => $stockQuantity,
            ]);
    }
}
