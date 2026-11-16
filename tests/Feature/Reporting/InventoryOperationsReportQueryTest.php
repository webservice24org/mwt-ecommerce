<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InventoryOperationsReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_reconciles_tracked_stock_states(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,
        ]);

        $this->createProduct(
            name: 'Healthy Stock Product',

            slug: 'healthy-stock-product',

            sku: 'INV-HEALTHY',

            trackInventory: true,

            stockQuantity: 10,
        );

        $this->createProduct(
            name: 'Low Stock Product',

            slug: 'low-stock-product',

            sku: 'INV-LOW',

            trackInventory: true,

            stockQuantity: 3,
        );

        $this->createProduct(
            name: 'Out Of Stock Product',

            slug: 'out-of-stock-product',

            sku: 'INV-OUT',

            trackInventory: true,

            stockQuantity: 0,
        );

        $this->createProduct(
            name: 'Untracked Product',

            slug: 'untracked-product',

            sku: 'INV-UNTRACKED',

            trackInventory: false,

            stockQuantity: 0,
        );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $summary =
            $result->summary;

        $this->assertSame(
            4,
            $summary->productsCount,
        );

        $this->assertSame(
            3,
            $summary->trackedProductsCount,
        );

        $this->assertSame(
            1,
            $summary->inStockCount,
        );

        $this->assertSame(
            1,
            $summary->lowStockCount,
        );

        $this->assertSame(
            1,
            $summary->outOfStockCount,
        );

        $this->assertSame(
            13,
            $summary->totalStockUnits,
        );

        $this->assertSame(
            5,
            $summary->lowStockThreshold,
        );
    }

    public function test_stock_state_filter_returns_only_matching_products(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,
        ]);

        $this->createProduct(
            name: 'In Stock Product',

            slug: 'in-stock-product',

            sku: 'INV-IN-STOCK',

            trackInventory: true,

            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Low Stock Product',

            slug: 'low-stock-product',

            sku: 'INV-LOW-STOCK',

            trackInventory: true,

            stockQuantity: 4,
        );

        $this->createProduct(
            name: 'Out Product',

            slug: 'out-product',

            sku: 'INV-OUT-STOCK',

            trackInventory: true,

            stockQuantity: 0,
        );

        $filters =
            new InventoryOperationsReportFiltersData(
                stockStates: [
                    'low_stock',
                ],
            );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->productsCount,
        );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'Low Stock Product',
            $result
                ->rows[0]
                ->name,
        );

        $this->assertSame(
            'low_stock',
            $result
                ->rows[0]
                ->stockState,
        );
    }

    public function test_search_matches_product_name_sku_and_slug(): void
    {
        $this->createProduct(
            name: 'Alpha Inventory Product',

            slug: 'alpha-inventory-product',

            sku: 'SKU-ALPHA-001',

            trackInventory: true,

            stockQuantity: 10,
        );

        $this->createProduct(
            name: 'Beta Product',

            slug: 'beta-special-slug',

            sku: 'SKU-BETA-001',

            trackInventory: true,

            stockQuantity: 10,
        );

        $this->createProduct(
            name: 'Gamma Product',

            slug: 'gamma-product',

            sku: 'SKU-GAMMA-001',

            trackInventory: true,

            stockQuantity: 10,
        );

        $query =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            );

        $nameResult =
            $query->get(
                new InventoryOperationsReportFiltersData(
                    search: 'Alpha Inventory',
                ),
            );

        $this->assertSame(
            1,
            $nameResult
                ->summary
                ->productsCount,
        );

        $this->assertSame(
            'Alpha Inventory Product',
            $nameResult
                ->rows[0]
                ->name,
        );

        $skuResult =
            $query->get(
                new InventoryOperationsReportFiltersData(
                    search: 'SKU-BETA-001',
                ),
            );

        $this->assertSame(
            1,
            $skuResult
                ->summary
                ->productsCount,
        );

        $this->assertSame(
            'Beta Product',
            $skuResult
                ->rows[0]
                ->name,
        );

        $slugResult =
            $query->get(
                new InventoryOperationsReportFiltersData(
                    search: 'gamma-product',
                ),
            );

        $this->assertSame(
            1,
            $slugResult
                ->summary
                ->productsCount,
        );

        $this->assertSame(
            'Gamma Product',
            $slugResult
                ->rows[0]
                ->name,
        );
    }

    public function test_untracked_product_is_not_counted_in_stock_buckets(): void
    {
        $this->createProduct(
            name: 'Untracked Product',

            slug: 'untracked-product',

            sku: 'INV-UNTRACKED',

            trackInventory: false,

            stockQuantity: 0,
        );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $summary =
            $result->summary;

        $this->assertSame(
            1,
            $summary->productsCount,
        );

        $this->assertSame(
            0,
            $summary->trackedProductsCount,
        );

        $this->assertSame(
            0,
            $summary->inStockCount,
        );

        $this->assertSame(
            0,
            $summary->lowStockCount,
        );

        $this->assertSame(
            0,
            $summary->outOfStockCount,
        );

        $this->assertSame(
            0,
            $summary->totalStockUnits,
        );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'not_tracked',
            $result
                ->rows[0]
                ->stockState,
        );
    }

    public function test_rows_are_prioritized_out_of_stock_then_low_stock_then_in_stock(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,
        ]);

        $this->createProduct(
            name: 'Healthy Product',

            slug: 'healthy-product',

            sku: 'INV-HEALTHY',

            trackInventory: true,

            stockQuantity: 25,
        );

        $this->createProduct(
            name: 'Low Product',

            slug: 'low-product',

            sku: 'INV-LOW',

            trackInventory: true,

            stockQuantity: 2,
        );

        $this->createProduct(
            name: 'Out Product',

            slug: 'out-product',

            sku: 'INV-OUT',

            trackInventory: true,

            stockQuantity: 0,
        );

        $this->createProduct(
            name: 'Untracked Product',

            slug: 'untracked-product',

            sku: 'INV-UNTRACKED',

            trackInventory: false,

            stockQuantity: 0,
        );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $this->assertCount(
            4,
            $result->rows,
        );

        $this->assertSame(
            'out_of_stock',
            $result
                ->rows[0]
                ->stockState,
        );

        $this->assertSame(
            'low_stock',
            $result
                ->rows[1]
                ->stockState,
        );

        $this->assertSame(
            'in_stock',
            $result
                ->rows[2]
                ->stockState,
        );

        $this->assertSame(
            'not_tracked',
            $result
                ->rows[3]
                ->stockState,
        );
    }

    public function test_configured_low_stock_threshold_is_respected(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 10,
        ]);

        $this->createProduct(
            name: 'Threshold Product',

            slug: 'threshold-product',

            sku: 'INV-THRESHOLD',

            trackInventory: true,

            stockQuantity: 8,
        );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $this->assertSame(
            10,
            $result
                ->summary
                ->lowStockThreshold,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->lowStockCount,
        );

        $this->assertSame(
            0,
            $result
                ->summary
                ->inStockCount,
        );

        $this->assertSame(
            'low_stock',
            $result
                ->rows[0]
                ->stockState,
        );
    }

    public function test_multiple_stock_state_filters_are_combined_with_or_semantics(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,
        ]);

        $this->createProduct(
            name: 'Healthy Product',

            slug: 'healthy-product',

            sku: 'INV-HEALTHY',

            trackInventory: true,

            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Low Product',

            slug: 'low-product',

            sku: 'INV-LOW',

            trackInventory: true,

            stockQuantity: 3,
        );

        $this->createProduct(
            name: 'Out Product',

            slug: 'out-product',

            sku: 'INV-OUT',

            trackInventory: true,

            stockQuantity: 0,
        );

        $filters =
            new InventoryOperationsReportFiltersData(
                stockStates: [
                    'low_stock',
                    'out_of_stock',
                ],
            );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            2,
            $result
                ->summary
                ->productsCount,
        );

        $this->assertCount(
            2,
            $result->rows,
        );

        $states =
            array_map(
                static fn (
                    $row,
                ): string => $row->stockState,

                $result->rows,
            );

        $this->assertSame(
            [
                'out_of_stock',
                'low_stock',
            ],
            $states,
        );
    }

    public function test_invalid_stock_state_values_are_ignored_by_filter_dto(): void
    {
        $filters =
            new InventoryOperationsReportFiltersData(
                stockStates: [
                    'low_stock',
                    'invalid-state',
                    'out_of_stock',
                ],
            );

        $this->assertSame(
            [
                'low_stock',
                'out_of_stock',
            ],
            $filters
                ->stockStateValues(),
        );
    }

    public function test_per_page_is_normalized_to_supported_values(): void
    {
        $valid =
            new InventoryOperationsReportFiltersData(
                perPage: 50,
            );

        $invalid =
            new InventoryOperationsReportFiltersData(
                perPage: 999,
            );

        $this->assertSame(
            50,
            $valid
                ->normalizedPerPage(),
        );

        $this->assertSame(
            25,
            $invalid
                ->normalizedPerPage(),
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

    public function test_rows_expose_inventory_alert_metadata(): void
    {
        config([
            'reporting.inventory_operations.low_stock_threshold' => 5,

            'reporting.inventory_operations.critical_stock_threshold' => 2,
        ]);

        $this->createProduct(
            name: 'Critical Product',

            slug: 'critical-product',

            sku: 'INV-CRITICAL',

            trackInventory: true,

            stockQuantity: 2,
        );

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $row =
            $result->rows[0];

        $this->assertSame(
            'critical_stock',
            $row
                ->alert
                ->state
                ->value,
        );

        $this->assertSame(
            'high',
            $row
                ->alert
                ->state
                ->severity(),
        );

        $this->assertTrue(
            $row
                ->alert
                ->requiresAttention(),
        );
    }

    public function test_inventory_alert_summary_reconciles_with_rows(): void
    {
        $this->createProduct(
            name: 'Healthy',
            slug: 'healthy',
            sku: 'INV-H',
            trackInventory: true,
            stockQuantity: 20,
        );

        $this->createProduct(
            name: 'Critical',
            slug: 'critical',
            sku: 'INV-C',
            trackInventory: true,
            stockQuantity: 1,
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

        $result =
            app(
                GetAdminInventoryOperationsReportQuery::class,
            )->get(
                new InventoryOperationsReportFiltersData,
            );

        $this->assertSame(
            3,
            $result
                ->summary
                ->attentionRequiredCount,
        );

        $this->assertSame(
            2,
            $result
                ->summary
                ->criticalAlertCount,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->highAlertCount,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->negativeStockCount,
        );
    }
}
