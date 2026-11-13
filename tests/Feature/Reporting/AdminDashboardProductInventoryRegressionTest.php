<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardInventoryAlertsQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardInventorySummaryQuery;
use App\Domain\Reporting\Queries\GetAdminDashboardTopProductsQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminDashboardProductInventoryRegressionTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardTopProductsQuery $topProductsQuery;

    private GetAdminDashboardInventoryAlertsQuery $inventoryAlertsQuery;

    private GetAdminDashboardInventorySummaryQuery $inventorySummaryQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->topProductsQuery =
            app(
                GetAdminDashboardTopProductsQuery::class,
            );

        $this->inventoryAlertsQuery =
            app(
                GetAdminDashboardInventoryAlertsQuery::class,
            );

        $this->inventorySummaryQuery =
            app(
                GetAdminDashboardInventorySummaryQuery::class,
            );
    }

    public function test_dashboard_product_and_inventory_data_requires_admin_authentication(): void
    {
        $this
            ->get(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_top_product_quantity_reconciles_with_non_cancelled_order_items(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $firstOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-05 10:00:00',
            );

        $secondOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-06 10:00:00',
            );

        $cancelledOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-07 10:00:00',

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            order: $firstOrder,

            product: $product,

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $secondOrder,

            product: $product,

            quantity: 3,

            lineTotal: 30_000,
        );

        $this->createItem(
            order: $cancelledOrder,

            product: $product,

            quantity: 50,

            lineTotal: 500_000,
        );

        $top =
            $this
                ->topProductsQuery
                ->get(
                    $this->novemberRange(),
                )[0];

        $this->assertSame(
            5,
            $top->quantitySold,
        );

        $this->assertSame(
            2,
            $top->ordersCount,
        );

        $this->assertSame(
            50_000,
            $top->grossSales,
        );
    }

    public function test_inventory_summary_buckets_reconcile_to_tracked_products(): void
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

                'stock_quantity' => 10,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => false,

                'stock_quantity' => 0,
            ]);

        $summary =
            $this
                ->inventorySummaryQuery
                ->get(
                    lowStockThreshold: 5,
                );

        $trackedBuckets =
            $summary
                ->inStockProductsCount
            + $summary
                ->lowStockProductsCount
            + $summary
                ->outOfStockProductsCount;

        $this->assertSame(
            $summary
                ->trackedProductsCount,
            $trackedBuckets,
        );

        $this->assertSame(
            3,
            $summary
                ->trackedProductsCount,
        );

        $this->assertSame(
            1,
            $summary
                ->untrackedProductsCount,
        );
    }

    public function test_inventory_alert_count_matches_low_and_out_of_stock_products_when_limit_does_not_truncate(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 2,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 10,
            ]);

        $summary =
            $this
                ->inventorySummaryQuery
                ->get(
                    lowStockThreshold: 5,
                );

        $alerts =
            $this
                ->inventoryAlertsQuery
                ->get(
                    threshold: 5,

                    limit: 50,
                );

        $expectedAlerts =
            $summary
                ->lowStockProductsCount
            + $summary
                ->outOfStockProductsCount;

        $this->assertSame(
            $expectedAlerts,
            count(
                $alerts,
            ),
        );
    }

    public function test_inventory_alert_rows_match_summary_threshold_semantics(): void
    {
        Product::factory()
            ->create([
                'name' => 'Out',

                'slug' => 'out',

                'track_inventory' => true,

                'stock_quantity' => 0,
            ]);

        Product::factory()
            ->create([
                'name' => 'Low',

                'slug' => 'low',

                'track_inventory' => true,

                'stock_quantity' => 5,
            ]);

        Product::factory()
            ->create([
                'name' => 'Healthy',

                'slug' => 'healthy',

                'track_inventory' => true,

                'stock_quantity' => 6,
            ]);

        $alerts =
            $this
                ->inventoryAlertsQuery
                ->get(
                    threshold: 5,

                    limit: 50,
                );

        $this->assertCount(
            2,
            $alerts,
        );

        foreach (
            $alerts as $alert
        ) {
            $this->assertLessThanOrEqual(
                5,
                $alert->stockQuantity,
            );

            if (
                $alert->stockQuantity <= 0
            ) {
                $this->assertTrue(
                    $alert->outOfStock,
                );
            } else {
                $this->assertFalse(
                    $alert->outOfStock,
                );
            }
        }
    }

    public function test_untracked_products_never_appear_in_inventory_alerts(): void
    {
        Product::factory()
            ->create([
                'name' => 'Untracked Zero',

                'slug' => 'untracked-zero',

                'track_inventory' => false,

                'stock_quantity' => 0,
            ]);

        $alerts =
            $this
                ->inventoryAlertsQuery
                ->get();

        $this->assertSame(
            [],
            $alerts,
        );
    }

    public function test_inventory_operations_are_not_affected_by_dashboard_date_range(): void
    {
        Product::factory()
            ->create([
                'track_inventory' => true,

                'stock_quantity' => 2,
            ]);

        $firstSummary =
            $this
                ->inventorySummaryQuery
                ->get();

        $secondSummary =
            $this
                ->inventorySummaryQuery
                ->get();

        $this->assertSame(
            $firstSummary
                ->toArray(),
            $secondSummary
                ->toArray(),
        );
    }

    public function test_top_products_remain_date_range_scoped(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $octoberOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-10-31 10:00:00',
            );

        $novemberOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',
            );

        $this->createItem(
            order: $octoberOrder,

            product: $product,

            quantity: 10,

            lineTotal: 100_000,
        );

        $this->createItem(
            order: $novemberOrder,

            product: $product,

            quantity: 2,

            lineTotal: 20_000,
        );

        $top =
            $this
                ->topProductsQuery
                ->get(
                    $this->novemberRange(),
                )[0];

        $this->assertSame(
            2,
            $top->quantitySold,
        );

        $this->assertSame(
            20_000,
            $top->grossSales,
        );
    }

    public function test_deleted_catalog_product_does_not_remove_historical_sales_reporting(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        $item =
            $this->createItem(
                order: $order,

                product: $product,

                quantity: 3,

                lineTotal: 30_000,

                productName: 'Deleted Historical Product',

                sku: 'DEL-001',
            );

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $top =
            $this
                ->topProductsQuery
                ->get(
                    $this->novemberRange(),
                )[0];

        $this->assertNull(
            $top->productId,
        );

        $this->assertSame(
            'Deleted Historical Product',
            $top->productName,
        );

        $this->assertSame(
            3,
            $top->quantitySold,
        );
    }

    public function test_dashboard_inertia_payload_reconciles_product_and_inventory_contracts(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Regression Product',

                    'slug' => 'regression-product',

                    'sku' => 'REG-001',

                    'track_inventory' => true,

                    'stock_quantity' => 4,
                ]);

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        $this->createItem(
            order: $order,

            product: $product,

            quantity: 2,

            lineTotal: 20_000,

            productName: 'Regression Product',

            sku: 'REG-001',
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'topProducts.0.product_id',
                        $product->id,
                    )
                    ->where(
                        'topProducts.0.quantity_sold',
                        2,
                    )
                    ->where(
                        'topProducts.0.gross_sales',
                        20_000,
                    )
                    ->where(
                        'inventorySummary.tracked_products_count',
                        1,
                    )
                    ->where(
                        'inventorySummary.low_stock_products_count',
                        1,
                    )
                    ->where(
                        'inventorySummary.out_of_stock_products_count',
                        0,
                    )
                    ->where(
                        'inventoryAlerts.0.product_id',
                        $product->id,
                    )
                    ->where(
                        'inventoryAlerts.0.stock_quantity',
                        4,
                    )
                    ->where(
                        'inventoryAlerts.0.out_of_stock',
                        false,
                    ),
            );
    }

    public function test_dashboard_frontend_contains_product_inventory_contracts_and_minor_unit_formatter(): void
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
            'topProducts',
            $source,
        );

        $this->assertStringContainsString(
            'inventorySummary',
            $source,
        );

        $this->assertStringContainsString(
            'inventoryAlerts',
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
            'Historical product',
            $source,
        );

        $this->assertStringContainsString(
            'value / 100',
            $source,
        );
    }

    private function novemberRange(
    ): ReportDateRangeData {
        return $this
            ->rangeFactory
            ->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-30',

                timezone: 'Asia/Dhaka',
            );
    }

    private function createOrder(
        User $customer,
        string $placedAt,
        OrderStatus $status =
        OrderStatus::Processing,
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => $status,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        Product $product,
        int $quantity,
        int $lineTotal,
        string $productName =
        'Regression Product',
        ?string $sku =
        'REG-001',
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->for(
                $product,
            )
            ->create([
                'product_name' => $productName,

                'sku' => $sku,

                'quantity' => $quantity,

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'line_total' => $lineTotal,
            ]);
    }
}
