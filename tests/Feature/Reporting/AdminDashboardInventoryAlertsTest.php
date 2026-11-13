<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Reporting\Queries\GetAdminDashboardInventoryAlertsQuery;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardInventoryAlertsTest extends TestCase
{
    use RefreshDatabase;

    private GetAdminDashboardInventoryAlertsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query =
            app(
                GetAdminDashboardInventoryAlertsQuery::class,
            );
    }

    public function test_low_stock_products_are_returned_at_or_below_threshold(): void
    {
        Product::factory()
            ->create([
                'name' => 'Zero Stock',

                'slug' => 'zero-stock',

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'name' => 'Five Stock',

                'slug' => 'five-stock',

                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        Product::factory()
            ->create([
                'name' => 'Six Stock',

                'slug' => 'six-stock',

                'track_inventory' => true,

                'stock_quantity' => 6,
            ]);

        $alerts =
            $this->query->get(
                threshold: 5,
            );

        $this->assertCount(
            2,
            $alerts,
        );

        $this->assertSame(
            'Zero Stock',
            $alerts[0]->name,
        );

        $this->assertSame(
            'Five Stock',
            $alerts[1]->name,
        );
    }

    public function test_out_of_stock_product_is_flagged(): void
    {
        Product::factory()
            ->create([
                'name' => 'Out Of Stock',

                'slug' => 'out-of-stock',

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        $alert =
            $this
                ->query
                ->get()[0];

        $this->assertSame(
            0,
            $alert->stockQuantity,
        );

        $this->assertTrue(
            $alert->outOfStock,
        );
    }

    public function test_low_but_non_zero_stock_is_not_out_of_stock(): void
    {
        Product::factory()
            ->create([
                'name' => 'Low Stock',

                'slug' => 'low-stock',

                'track_inventory' => true,

                'stock_quantity' => 3,
            ]);

        $alert =
            $this
                ->query
                ->get()[0];

        $this->assertSame(
            3,
            $alert->stockQuantity,
        );

        $this->assertFalse(
            $alert->outOfStock,
        );
    }

    public function test_products_not_tracking_inventory_are_excluded(): void
    {
        Product::factory()
            ->create([
                'name' => 'Unlimited Product',

                'slug' => 'unlimited-product',

                'track_inventory' => false,

                'stock_quantity' => 0,
            ]);

        $alerts =
            $this->query->get();

        $this->assertSame(
            [],
            $alerts,
        );
    }

    public function test_products_above_threshold_are_excluded(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        $alerts =
            $this->query->get(
                threshold: 5,
            );

        $this->assertSame(
            [],
            $alerts,
        );
    }

    public function test_custom_threshold_is_respected(): void
    {
        Product::factory()
            ->create([
                'name' => 'Seven Stock',

                'slug' => 'seven-stock',

                'track_inventory' => true,

                'stock_quantity' => 7,
            ]);

        $defaultAlerts =
            $this->query->get();

        $customAlerts =
            $this->query->get(
                threshold: 10,
            );

        $this->assertCount(
            0,
            $defaultAlerts,
        );

        $this->assertCount(
            1,
            $customAlerts,
        );
    }

    public function test_negative_threshold_is_normalized_to_zero(): void
    {
        Product::factory()
            ->create([
                'name' => 'Out Of Stock',

                'slug' => 'out-of-stock',

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'name' => 'One Stock',

                'slug' => 'one-stock',

                'track_inventory' => true,

                'stock_quantity' => 1,
            ]);

        $alerts =
            $this->query->get(
                threshold: -100,
            );

        $this->assertCount(
            1,
            $alerts,
        );

        $this->assertSame(
            0,
            $alerts[0]->lowStockThreshold,
        );

        $this->assertTrue(
            $alerts[0]->outOfStock,
        );
    }

    public function test_alerts_are_ordered_by_stock_then_name(): void
    {
        Product::factory()
            ->create([
                'name' => 'Zulu',

                'slug' => 'zulu',

                'track_inventory' => true,

                'stock_quantity' => 2,
            ]);

        Product::factory()
            ->create([
                'name' => 'Alpha',

                'slug' => 'alpha',

                'track_inventory' => true,

                'stock_quantity' => 2,
            ]);

        Product::factory()
            ->create([
                'name' => 'Zero',

                'slug' => 'zero',

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        $alerts =
            $this->query->get();

        $this->assertSame(
            [
                'Zero',
                'Alpha',
                'Zulu',
            ],
            array_map(
                static fn (
                    $alert,
                ): string => $alert->name,
                $alerts,
            ),
        );
    }

    public function test_limit_is_respected(): void
    {
        for (
            $index = 1;
            $index <= 10;
            $index++
        ) {
            Product::factory()
                ->create([
                    'track_inventory' => true,

                    'stock_quantity' => 1,
                ]);
        }

        $alerts =
            $this->query->get(
                limit: 4,
            );

        $this->assertCount(
            4,
            $alerts,
        );
    }

    public function test_alert_includes_product_status_without_filtering_it_out(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Low Stock Product',

                    'slug' => 'low-stock-product',

                    'status' => ProductStatus::Published,

                    'track_inventory' => true,

                    'stock_quantity' => 2,
                ]);

        $alert =
            $this
                ->query
                ->get()[0];

        $this->assertSame(
            $product->id,
            $alert->productId,
        );

        $this->assertSame(
            ProductStatus::Published
                ->value,
            $alert->status,
        );
    }

    public function test_alert_array_contract_is_stable(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Contract Product',

                    'slug' => 'contract-product',

                    'sku' => 'CONTRACT-001',

                    'track_inventory' => true,

                    'stock_quantity' => 4,
                ]);

        $payload =
            $this
                ->query
                ->get(
                    threshold: 5,
                )[0]
                ->toArray();

        $this->assertSame(
            [
                'product_id',
                'name',
                'slug',
                'sku',
                'status',
                'stock_quantity',
                'low_stock_threshold',
                'out_of_stock',
            ],
            array_keys(
                $payload,
            ),
        );

        $this->assertSame(
            $product->id,
            $payload[
                'product_id'
            ],
        );

        $this->assertSame(
            'CONTRACT-001',
            $payload[
                'sku'
            ],
        );

        $this->assertSame(
            4,
            $payload[
                'stock_quantity'
            ],
        );

        $this->assertSame(
            5,
            $payload[
                'low_stock_threshold'
            ],
        );

        $this->assertFalse(
            $payload[
                'out_of_stock'
            ],
        );
    }
}
