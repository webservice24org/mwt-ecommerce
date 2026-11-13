<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Queries\GetAdminDashboardInventorySummaryQuery;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardInventorySummaryTest extends TestCase
{
    use RefreshDatabase;

    private GetAdminDashboardInventorySummaryQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query =
            app(
                GetAdminDashboardInventorySummaryQuery::class,
            );
    }

    public function test_inventory_summary_counts_tracked_and_untracked_products(): void
    {
        Product::factory()
            ->count(
                3,
            )
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        Product::factory()
            ->count(
                2,
            )
            ->create([
                'track_inventory' => false,

                'stock_quantity' => 0,
            ]);

        $summary =
            $this->query->get();

        $this->assertSame(
            3,
            $summary->trackedProductsCount,
        );

        $this->assertSame(
            2,
            $summary->untrackedProductsCount,
        );
    }

    public function test_inventory_summary_classifies_stock_buckets(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 3,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 6,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 20,
            ]);

        $summary =
            $this->query->get(
                lowStockThreshold: 5,
            );

        $this->assertSame(
            1,
            $summary->outOfStockProductsCount,
        );

        $this->assertSame(
            2,
            $summary->lowStockProductsCount,
        );

        $this->assertSame(
            2,
            $summary->inStockProductsCount,
        );
    }

    public function test_total_units_in_stock_only_includes_tracked_products(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => false,

                'stock_quantity' => 999,
            ]);

        $summary =
            $this->query->get();

        $this->assertSame(
            15,
            $summary->totalUnitsInStock,
        );
    }

    public function test_custom_threshold_changes_low_and_in_stock_classification(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 7,
            ]);

        $defaultSummary =
            $this->query->get(
                lowStockThreshold: 5,
            );

        $customSummary =
            $this->query->get(
                lowStockThreshold: 10,
            );

        $this->assertSame(
            1,
            $defaultSummary
                ->inStockProductsCount,
        );

        $this->assertSame(
            0,
            $defaultSummary
                ->lowStockProductsCount,
        );

        $this->assertSame(
            0,
            $customSummary
                ->inStockProductsCount,
        );

        $this->assertSame(
            1,
            $customSummary
                ->lowStockProductsCount,
        );
    }

    public function test_negative_threshold_is_normalized_to_zero(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 1,
            ]);

        $summary =
            $this->query->get(
                lowStockThreshold: -5,
            );

        $this->assertSame(
            0,
            $summary->lowStockThreshold,
        );

        $this->assertSame(
            1,
            $summary
                ->outOfStockProductsCount,
        );

        $this->assertSame(
            0,
            $summary
                ->lowStockProductsCount,
        );

        $this->assertSame(
            1,
            $summary
                ->inStockProductsCount,
        );
    }

    public function test_zero_threshold_has_no_low_stock_bucket(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 1,
            ]);

        $summary =
            $this->query->get(
                lowStockThreshold: 0,
            );

        $this->assertSame(
            1,
            $summary
                ->outOfStockProductsCount,
        );

        $this->assertSame(
            0,
            $summary
                ->lowStockProductsCount,
        );

        $this->assertSame(
            1,
            $summary
                ->inStockProductsCount,
        );
    }

    public function test_empty_catalog_returns_all_zeroes(): void
    {
        $summary =
            $this->query->get();

        $this->assertSame(
            [
                'tracked_products_count' => 0,

                'in_stock_products_count' => 0,

                'low_stock_products_count' => 0,

                'out_of_stock_products_count' => 0,

                'untracked_products_count' => 0,

                'total_units_in_stock' => 0,

                'low_stock_threshold' => 5,
            ],
            $summary->toArray(),
        );
    }

    public function test_summary_bucket_counts_reconcile_to_tracked_products(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 4,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        $summary =
            $this->query->get();

        $bucketTotal =
            $summary
                ->outOfStockProductsCount
            + $summary
                ->lowStockProductsCount
            + $summary
                ->inStockProductsCount;

        $this->assertSame(
            $summary
                ->trackedProductsCount,
            $bucketTotal,
        );
    }

    public function test_array_contract_is_stable(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 3,
            ]);

        $payload =
            $this
                ->query
                ->get()
                ->toArray();

        $this->assertSame(
            [
                'tracked_products_count',
                'in_stock_products_count',
                'low_stock_products_count',
                'out_of_stock_products_count',
                'untracked_products_count',
                'total_units_in_stock',
                'low_stock_threshold',
            ],
            array_keys(
                $payload,
            ),
        );
    }
}
