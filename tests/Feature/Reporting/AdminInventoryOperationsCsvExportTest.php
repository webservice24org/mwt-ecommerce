<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminInventoryOperationsCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_inventory_operations_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.inventory.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_export_inventory_operations_csv(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Inventory Export Product',

            slug: 'inventory-export-product',

            sku: 'INV-CSV-001',

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
                        'admin.reports.operations.inventory.csv',
                    ),
                );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );

        $csv =
            $response
                ->streamedContent();

        $this->assertStringContainsString(
            'Product Name',
            $csv,
        );

        $this->assertStringContainsString(
            'Inventory Export Product',
            $csv,
        );

        $this->assertStringContainsString(
            'INV-CSV-001',
            $csv,
        );

        $this->assertStringContainsString(
            'low_stock',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );
    }

    public function test_csv_respects_stock_state_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Low Export Product',

            slug: 'low-export-product',

            sku: 'INV-LOW-CSV',

            trackInventory: true,

            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Healthy Export Product',

            slug: 'healthy-export-product',

            sku: 'INV-HEALTHY-CSV',

            trackInventory: true,

            stockQuantity: 20,
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
                            'stock_state' => [
                                'low_stock',
                            ],
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'Low Export Product',
            $csv,
        );

        $this->assertStringNotContainsString(
            'Healthy Export Product',
            $csv,
        );
    }

    public function test_csv_respects_search_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Target Inventory Product',

            slug: 'target-inventory-product',

            sku: 'INV-TARGET-CSV',

            trackInventory: true,

            stockQuantity: 8,
        );

        $this->createProduct(
            name: 'Other Inventory Product',

            slug: 'other-inventory-product',

            sku: 'INV-OTHER-CSV',

            trackInventory: true,

            stockQuantity: 8,
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
                            'search' => 'INV-TARGET-CSV',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'Target Inventory Product',
            $csv,
        );

        $this->assertStringNotContainsString(
            'Other Inventory Product',
            $csv,
        );
    }

    public function test_csv_contains_alert_metadata(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Critical Inventory Product',

            slug: 'critical-inventory-product',

            sku: 'INV-CRIT-CSV',

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
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'Alert State',
            $csv,
        );

        $this->assertStringContainsString(
            'Alert Severity',
            $csv,
        );

        $this->assertStringContainsString(
            'Alert Priority',
            $csv,
        );

        $this->assertStringContainsString(
            'Requires Attention',
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
    }

    public function test_negative_stock_exports_as_distinct_alert_and_stock_state(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Negative Export Product',

            slug: 'negative-export-product',

            sku: 'INV-NEG-CSV',

            trackInventory: true,

            stockQuantity: -3,
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
                            'search' => 'INV-NEG-CSV',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'negative_stock',
            $csv,
        );

        $this->assertStringContainsString(
            'critical',
            $csv,
        );

        $this->assertStringContainsString(
            '90',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );
    }

    public function test_untracked_product_exports_not_tracked_without_attention(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: 'Untracked Export Product',

            slug: 'untracked-export-product',

            sku: 'INV-UNTRACKED-CSV',

            trackInventory: false,

            stockQuantity: 0,
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
                            'search' => 'INV-UNTRACKED-CSV',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'not_tracked',
            $csv,
        );

        $this->assertStringContainsString(
            'No',
            $csv,
        );
    }

    public function test_csv_protects_against_spreadsheet_formula_injection(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this->createProduct(
            name: '=HYPERLINK("malicious")',

            slug: 'formula-product',

            sku: '+INV-FORMULA',

            trackInventory: true,

            stockQuantity: 10,
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
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            '\'=HYPERLINK',
            $csv,
        );

        $this->assertStringContainsString(
            "'+INV-FORMULA",
            $csv,
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
