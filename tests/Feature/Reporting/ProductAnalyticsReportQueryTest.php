<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductAnalyticsReportQuery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductAnalyticsReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_authoritative_product_analytics_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Analytics Product',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-ANALYTICS-001',
                placedAt: '2026-11-10 10:00:00',
            );

        $this->createItem(
            order: $firstOrder,
            product: $product,
            productName: 'Analytics Product',
            sku: 'ANALYTICS-001',
            quantity: 2,
            lineTotal: 10_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-ANALYTICS-002',
                placedAt: '2026-11-11 10:00:00',
            );

        $this->createItem(
            order: $secondOrder,
            product: $product,
            productName: 'Analytics Product',
            sku: 'ANALYTICS-001',
            quantity: 3,
            lineTotal: 15_000,
        );

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->productGroupsCount,
        );

        $this->assertSame(
            5,
            $result
                ->summary
                ->quantitySold,
        );

        $this->assertSame(
            2,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            25_000,
            $result
                ->summary
                ->grossSales,
        );

        $this->assertSame(
            12_500,
            $result
                ->summary
                ->averageRevenuePerOrder,
        );

        $this->assertSame(
            5_000,
            $result
                ->summary
                ->averageRevenuePerItem,
        );

        $this->assertSame(
            25_000,
            $result
                ->summary
                ->currentProductRevenue,
        );

        $this->assertSame(
            0,
            $result
                ->summary
                ->historicalProductRevenue,
        );
    }

    public function test_same_order_with_multiple_products_is_counted_once_in_summary_orders(): void
    {
        $customer =
            User::factory()
                ->create();

        $firstProduct =
            Product::factory()
                ->create();

        $secondProduct =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'MULTI-PRODUCT-001',
                placedAt: '2026-11-12 10:00:00',
            );

        $this->createItem(
            order: $order,
            product: $firstProduct,
            productName: 'Product One',
            sku: 'ONE',
            quantity: 1,
            lineTotal: 5_000,
        );

        $this->createItem(
            order: $order,
            product: $secondProduct,
            productName: 'Product Two',
            sku: 'TWO',
            quantity: 1,
            lineTotal: 7_000,
        );

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            2,
            $result
                ->summary
                ->productGroupsCount,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            12_000,
            $result
                ->summary
                ->grossSales,
        );
    }

    public function test_deleted_product_is_reported_as_historical_revenue(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Historical Analytics Product',
                ]);

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'HISTORICAL-ANALYTICS-001',
                placedAt: '2026-11-13 10:00:00',
            );

        $item =
            $this->createItem(
                order: $order,
                product: $product,
                productName: 'Historical Analytics Product',
                sku: 'HIST-001',
                quantity: 2,
                lineTotal: 9_000,
            );

        $item->update([
            'product_id' => null,
        ]);

        $product->delete();

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            0,
            $result
                ->summary
                ->currentProductRevenue,
        );

        $this->assertSame(
            9_000,
            $result
                ->summary
                ->historicalProductRevenue,
        );

        $this->assertCount(
            1,
            $result->products,
        );

        $this->assertTrue(
            $result
                ->products[0]
                ->historical,
        );

        $this->assertNull(
            $result
                ->products[0]
                ->productId,
        );

        $this->assertSame(
            'Historical Analytics Product',
            $result
                ->products[0]
                ->productName,
        );
    }

    public function test_daily_trend_is_ascending_and_reconciles_with_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $second =
            $this->createOrder(
                user: $customer,
                orderNumber: 'TREND-002',
                placedAt: '2026-11-12 10:00:00',
            );

        $this->createItem(
            order: $second,
            product: $product,
            productName: 'Trend Product',
            sku: 'TREND',
            quantity: 2,
            lineTotal: 12_000,
        );

        $first =
            $this->createOrder(
                user: $customer,
                orderNumber: 'TREND-001',
                placedAt: '2026-11-10 10:00:00',
            );

        $this->createItem(
            order: $first,
            product: $product,
            productName: 'Trend Product',
            sku: 'TREND',
            quantity: 1,
            lineTotal: 6_000,
        );

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertCount(
            2,
            $result->trend,
        );

        $this->assertSame(
            '2026-11-10',
            $result
                ->trend[0]
                ->date,
        );

        $this->assertSame(
            '2026-11-12',
            $result
                ->trend[1]
                ->date,
        );

        $trendGrossSales =
            array_sum(
                array_map(
                    static fn ($row): int => $row->grossSales,
                    $result->trend,
                ),
            );

        $this->assertSame(
            $result
                ->summary
                ->grossSales,
            $trendGrossSales,
        );
    }

    public function test_search_matches_product_name_and_sku(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'SEARCH-001',
                placedAt: '2026-11-14 10:00:00',
            );

        $this->createItem(
            order: $order,
            product: $product,
            productName: 'Special Search Product',
            sku: 'SPECIAL-SKU',
            quantity: 1,
            lineTotal: 8_000,
        );

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(
                    search: 'SPECIAL-SKU',
                ),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->productGroupsCount,
        );

        $this->assertSame(
            8_000,
            $result
                ->summary
                ->grossSales,
        );
    }

    public function test_cancelled_orders_are_not_implicitly_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'CANCELLED-PRODUCT-ANALYTICS',
                placedAt: '2026-11-15 10:00:00',
                status: OrderStatus::Cancelled,
                paymentStatus: PaymentStatus::Unpaid,
            );

        $this->createItem(
            order: $order,
            product: $product,
            productName: 'Cancelled Product',
            sku: 'CANCELLED',
            quantity: 1,
            lineTotal: 7_000,
        );

        $result =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            7_000,
            $result
                ->summary
                ->grossSales,
        );
    }

    private function filters(
        ?string $search = null,
    ): ProductPerformanceReportFiltersData {
        return new ProductPerformanceReportFiltersData(
            range: new ReportDateRangeData(
                preset: ReportDatePreset::Custom,

                fromInclusive: CarbonImmutable::parse(
                    '2026-11-01 00:00:00',
                    'Asia/Dhaka',
                ),

                toExclusive: CarbonImmutable::parse(
                    '2026-12-01 00:00:00',
                    'Asia/Dhaka',
                ),

                timezone: 'Asia/Dhaka',
            ),

            statuses: [],

            paymentStatuses: [],

            search: $search,

            perPage: 25,
        );
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status =
        OrderStatus::Completed,
        PaymentStatus $paymentStatus =
        PaymentStatus::Paid,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'order_number' => $orderNumber,

                'status' => $status,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'currency_code' => 'BDT',

                'subtotal' => 0,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 0,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        ?Product $product,
        string $productName,
        ?string $sku,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product?->id,

                'product_name' => $productName,

                'sku' => $sku,

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
