<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminInventoryOperationsFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory_report_or_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.inventory',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.operations.inventory.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_inventory_report_and_csv(): void
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory.csv',
                ),
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );
    }

    public function test_summary_reconciles_stock_states_and_alert_counts(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Healthy Product',
            slug: 'healthy-product',
            sku: 'INV-FINAL-H',
            trackInventory: true,
            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Low Product',
            slug: 'low-product',
            sku: 'INV-FINAL-L',
            trackInventory: true,
            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Critical Product',
            slug: 'critical-product',
            sku: 'INV-FINAL-C',
            trackInventory: true,
            stockQuantity: 2,
        );

        $this->createProduct(
            name: 'Out Product',
            slug: 'out-product',
            sku: 'INV-FINAL-O',
            trackInventory: true,
            stockQuantity: 0,
        );

        $this->createProduct(
            name: 'Negative Product',
            slug: 'negative-product',
            sku: 'INV-FINAL-N',
            trackInventory: true,
            stockQuantity: -3,
        );

        $this->createProduct(
            name: 'Untracked Product',
            slug: 'untracked-product',
            sku: 'INV-FINAL-U',
            trackInventory: false,
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
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.products_count',
                        6,
                    )
                    ->where(
                        'summary.tracked_products_count',
                        5,
                    )
                    ->where(
                        'summary.in_stock_count',
                        1,
                    )
                    ->where(
                        'summary.low_stock_count',
                        2,
                    )
                    ->where(
                        'summary.out_of_stock_count',
                        1,
                    )
                    ->where(
                        'summary.negative_stock_count',
                        1,
                    )
                    ->where(
                        'summary.attention_required_count',
                        4,
                    )
                    ->where(
                        'summary.critical_alert_count',
                        2,
                    )
                    ->where(
                        'summary.high_alert_count',
                        1,
                    )
                    ->has(
                        'rows',
                        6,
                    ),
            );
    }

    public function test_rows_are_prioritized_by_inventory_urgency(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Healthy',
            slug: 'healthy',
            sku: 'INV-H',
            trackInventory: true,
            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Low',
            slug: 'low',
            sku: 'INV-L',
            trackInventory: true,
            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Critical',
            slug: 'critical',
            sku: 'INV-C',
            trackInventory: true,
            stockQuantity: 2,
        );

        $this->createProduct(
            name: 'Out',
            slug: 'out',
            sku: 'INV-O',
            trackInventory: true,
            stockQuantity: 0,
        );

        $this->createProduct(
            name: 'Negative',
            slug: 'negative',
            sku: 'INV-N',
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
                        'rows.0.alert.state',
                        'negative_stock',
                    )
                    ->where(
                        'rows.1.alert.state',
                        'out_of_stock',
                    )
                    ->where(
                        'rows.2.alert.state',
                        'critical_stock',
                    )
                    ->where(
                        'rows.3.alert.state',
                        'low_stock',
                    )
                    ->where(
                        'rows.4.alert.state',
                        'healthy',
                    ),
            );
    }

    public function test_browser_and_csv_use_same_stock_state_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Low Match',
            slug: 'low-match',
            sku: 'INV-LOW-MATCH',
            trackInventory: true,
            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Healthy Other',
            slug: 'healthy-other',
            sku: 'INV-HEALTHY-OTHER',
            trackInventory: true,
            stockQuantity: 20,
        );

        $filters = [
            'stock_state' => [
                'low_stock',
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
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
                        'INV-LOW-MATCH',
                    ),
            );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.inventory.csv',
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'INV-LOW-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'INV-HEALTHY-OTHER',
            $csv,
        );
    }

    public function test_browser_and_csv_use_same_search_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Target Product',
            slug: 'target-product',
            sku: 'INV-TARGET-FINAL',
            trackInventory: true,
            stockQuantity: 8,
        );

        $this->createProduct(
            name: 'Other Product',
            slug: 'other-product',
            sku: 'INV-OTHER-FINAL',
            trackInventory: true,
            stockQuantity: 8,
        );

        $filters = [
            'search' => 'INV-TARGET-FINAL',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.inventory',
                    $filters,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->where(
                        'rows.0.sku',
                        'INV-TARGET-FINAL',
                    ),
            );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.inventory.csv',
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'INV-TARGET-FINAL',
            $csv,
        );

        $this->assertStringNotContainsString(
            'INV-OTHER-FINAL',
            $csv,
        );
    }

    public function test_negative_stock_is_distinct_from_out_of_stock(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Negative Stock',
            slug: 'negative-stock',
            sku: 'INV-NEG-DISTINCT',
            trackInventory: true,
            stockQuantity: -1,
        );

        $this->createProduct(
            name: 'Zero Stock',
            slug: 'zero-stock',
            sku: 'INV-ZERO-DISTINCT',
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
                        'summary.products_count',
                        1,
                    )
                    ->where(
                        'summary.negative_stock_count',
                        1,
                    )
                    ->where(
                        'rows.0.sku',
                        'INV-NEG-DISTINCT',
                    )
                    ->where(
                        'rows.0.stock_state',
                        'negative_stock',
                    )
                    ->where(
                        'rows.0.alert.state',
                        'negative_stock',
                    ),
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
                            'out_of_stock',
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
                        'summary.products_count',
                        1,
                    )
                    ->where(
                        'rows.0.sku',
                        'INV-ZERO-DISTINCT',
                    )
                    ->where(
                        'rows.0.stock_state',
                        'out_of_stock',
                    ),
            );
    }

    public function test_untracked_products_are_not_counted_in_stock_buckets(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Untracked Final',
            slug: 'untracked-final',
            sku: 'INV-UNTRACKED-FINAL',
            trackInventory: false,
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
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.products_count',
                        1,
                    )
                    ->where(
                        'summary.tracked_products_count',
                        0,
                    )
                    ->where(
                        'summary.in_stock_count',
                        0,
                    )
                    ->where(
                        'summary.low_stock_count',
                        0,
                    )
                    ->where(
                        'summary.out_of_stock_count',
                        0,
                    )
                    ->where(
                        'summary.negative_stock_count',
                        0,
                    )
                    ->where(
                        'summary.attention_required_count',
                        0,
                    )
                    ->where(
                        'rows.0.stock_state',
                        'not_tracked',
                    )
                    ->where(
                        'rows.0.alert.state',
                        'not_tracked',
                    )
                    ->where(
                        'rows.0.alert.requires_attention',
                        false,
                    ),
            );
    }

    public function test_configured_thresholds_are_applied_consistently(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 10,

            'reporting.inventory_operations.critical_stock_threshold' => 4,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Critical Threshold Product',
            slug: 'critical-threshold-product',
            sku: 'INV-THRESHOLD-C',
            trackInventory: true,
            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Low Threshold Product',
            slug: 'low-threshold-product',
            sku: 'INV-THRESHOLD-L',
            trackInventory: true,
            stockQuantity: 8,
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
                        'summary.low_stock_threshold',
                        10,
                    )
                    ->where(
                        'summary.low_stock_count',
                        2,
                    )
                    ->where(
                        'summary.high_alert_count',
                        1,
                    )
                    ->where(
                        'rows.0.alert.state',
                        'critical_stock',
                    )
                    ->where(
                        'rows.1.alert.state',
                        'low_stock',
                    ),
            );
    }

    public function test_invalid_inventory_filters_are_rejected(): void
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
                            'danger_zone',
                        ],

                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'stock_state.0',
                'per_page',
            ]);
    }

    public function test_csv_contains_authoritative_alert_metadata(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Critical CSV Final',
            slug: 'critical-csv-final',
            sku: 'INV-CSV-FINAL',
            trackInventory: true,
            stockQuantity: 2,
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.inventory.csv',
                        [
                            'search' => 'INV-CSV-FINAL',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'INV-CSV-FINAL',
            $csv,
        );

        $this->assertStringContainsString(
            'critical_stock',
            $csv,
        );

        $this->assertStringContainsString(
            'high',
            $csv,
        );

        $this->assertStringContainsString(
            '50',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );
    }

    public function test_frontend_contains_required_inventory_workflow_contracts(): void
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
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'Clear search',
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

        $this->assertStringContainsString(
            'admin.products.edit',
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
            'preserveState',
            $source,
        );

        $this->assertStringContainsString(
            'preserveScroll',
            $source,
        );

        $this->assertStringContainsString(
            'MobileProductCard',
            $source,
        );
    }

    public function test_inventory_sidebar_navigation_contract_is_present(): void
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

    public function test_inventory_report_and_export_routes_are_registered(): void
    {
        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.inventory',
            ),
        );

        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.inventory.csv',
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

                'track_inventory' => $trackInventory,

                'stock_quantity' => $stockQuantity,
            ]);
    }
}
