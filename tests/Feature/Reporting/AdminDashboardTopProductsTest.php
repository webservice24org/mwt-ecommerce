<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardTopProductsQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardTopProductsTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardTopProductsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardTopProductsQuery::class,
            );
    }

    public function test_top_products_are_ranked_by_quantity_sold(): void
    {
        $customer =
            User::factory()
                ->create();

        $firstOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 10:00:00',
            );

        $secondOrder =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-02 10:00:00',
            );

        $productA =
            Product::factory()
                ->create();

        $productB =
            Product::factory()
                ->create();

        $this->createItem(
            order: $firstOrder,

            product: $productA,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $secondOrder,

            product: $productA,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 3,

            lineTotal: 30_000,
        );

        $this->createItem(
            order: $firstOrder,

            product: $productB,

            productName: 'Product B',

            sku: 'SKU-B',

            quantity: 4,

            lineTotal: 40_000,
        );

        $products =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            2,
            $products,
        );

        $this->assertSame(
            'Product A',
            $products[0]->productName,
        );

        $this->assertSame(
            5,
            $products[0]->quantitySold,
        );

        $this->assertSame(
            2,
            $products[0]->ordersCount,
        );

        $this->assertSame(
            50_000,
            $products[0]->grossSales,
        );

        $this->assertSame(
            'Product B',
            $products[1]->productName,
        );

        $this->assertSame(
            4,
            $products[1]->quantitySold,
        );
    }

    public function test_cancelled_orders_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $active =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-05 10:00:00',
            );

        $cancelled =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-06 10:00:00',

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            order: $active,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $cancelled,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 50,

            lineTotal: 500_000,
        );

        $products =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            1,
            $products,
        );

        $this->assertSame(
            2,
            $products[0]->quantitySold,
        );

        $this->assertSame(
            20_000,
            $products[0]->grossSales,
        );
    }

    public function test_deleted_product_snapshot_remains_in_report(): void
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

                productName: 'Historical Product',

                sku: 'HIST-001',

                quantity: 3,

                lineTotal: 30_000,
            );

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $products =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            1,
            $products,
        );

        $this->assertNull(
            $products[0]->productId,
        );

        $this->assertSame(
            'Historical Product',
            $products[0]->productName,
        );

        $this->assertSame(
            'HIST-001',
            $products[0]->sku,
        );

        $this->assertSame(
            3,
            $products[0]->quantitySold,
        );

        $this->assertSame(
            30_000,
            $products[0]->grossSales,
        );
    }

    public function test_orders_count_is_distinct_per_product(): void
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

        $this->createItem(
            order: $order,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $this->createItem(
            order: $order,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 1,

            lineTotal: 10_000,
        );

        $products =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            3,
            $products[0]->quantitySold,
        );

        $this->assertSame(
            1,
            $products[0]->ordersCount,
        );

        $this->assertSame(
            30_000,
            $products[0]->grossSales,
        );
    }

    public function test_orders_outside_selected_range_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $outside =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-10-31 23:59:59',
            );

        $inside =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-01 00:00:00',
            );

        $this->createItem(
            order: $outside,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 10,

            lineTotal: 100_000,
        );

        $this->createItem(
            order: $inside,

            product: $product,

            productName: 'Product A',

            sku: 'SKU-A',

            quantity: 2,

            lineTotal: 20_000,
        );

        $range =
            $this
                ->rangeFactory
                ->make(
                    preset: ReportDatePreset::Custom,

                    from: '2026-11-01',

                    to: '2026-11-01',

                    timezone: 'Asia/Dhaka',
                );

        $products =
            $this->query->get(
                $range,
            );

        $this->assertSame(
            2,
            $products[0]->quantitySold,
        );

        $this->assertSame(
            20_000,
            $products[0]->grossSales,
        );
    }

    public function test_limit_is_bounded(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        for (
            $index = 1;
            $index <= 15;
            $index++
        ) {
            $product =
                Product::factory()
                    ->create();

            $this->createItem(
                order: $order,

                product: $product,

                productName: sprintf(
                    'Product %02d',
                    $index,
                ),

                sku: sprintf(
                    'SKU-%02d',
                    $index,
                ),

                quantity: $index,

                lineTotal: $index
                * 1_000,
            );
        }

        $products =
            $this->query->get(
                range: $this->novemberRange(),

                limit: 5,
            );

        $this->assertCount(
            5,
            $products,
        );

        $this->assertSame(
            15,
            $products[0]->quantitySold,
        );
    }

    public function test_ties_are_ordered_by_sales_then_product_name(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                customer: $customer,

                placedAt: '2026-11-10 10:00:00',
            );

        $productA =
            Product::factory()
                ->create();

        $productB =
            Product::factory()
                ->create();

        $productC =
            Product::factory()
                ->create();

        $this->createItem(
            order: $order,

            product: $productA,

            productName: 'Bravo',

            sku: 'BRAVO',

            quantity: 5,

            lineTotal: 50_000,
        );

        $this->createItem(
            order: $order,

            product: $productB,

            productName: 'Charlie',

            sku: 'CHARLIE',

            quantity: 5,

            lineTotal: 60_000,
        );

        $this->createItem(
            order: $order,

            product: $productC,

            productName: 'Alpha',

            sku: 'ALPHA',

            quantity: 5,

            lineTotal: 50_000,
        );

        $products =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            'Charlie',
            $products[0]->productName,
        );

        $this->assertSame(
            'Alpha',
            $products[1]->productName,
        );

        $this->assertSame(
            'Bravo',
            $products[2]->productName,
        );
    }

    public function test_array_contract_is_stable_and_money_remains_integer(): void
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

        $this->createItem(
            order: $order,

            product: $product,

            productName: 'Contract Product',

            sku: 'CONTRACT',

            quantity: 3,

            lineTotal: 123_456,
        );

        $payload =
            $this
                ->query
                ->get(
                    $this->novemberRange(),
                )[0]
                ->toArray();

        $this->assertSame(
            [
                'product_id',
                'product_name',
                'sku',
                'quantity_sold',
                'orders_count',
                'gross_sales',
            ],
            array_keys(
                $payload,
            ),
        );

        $this->assertSame(
            3,
            $payload[
                'quantity_sold'
            ],
        );

        $this->assertSame(
            1,
            $payload[
                'orders_count'
            ],
        );

        $this->assertSame(
            123_456,
            $payload[
                'gross_sales'
            ],
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
        string $productName,
        ?string $sku,
        int $quantity,
        int $lineTotal,
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
