<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardMetricsQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardMetricsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardMetricsQuery::class,
            );
    }

    public function test_dashboard_metrics_are_calculated_from_authoritative_order_snapshots(): void
    {
        $customer =
            User::factory()
                ->create();

        $firstOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-10 10:00:00',

                paymentStatus: PaymentStatus::Paid,

                subtotal: 10_000,

                discount: 1_000,

                shipping: 800,

                tax: 200,

                grandTotal: 10_000,
            );

        $secondOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-11 14:00:00',

                paymentStatus: PaymentStatus::Due,

                subtotal: 20_000,

                discount: 2_000,

                shipping: 1_200,

                tax: 300,

                grandTotal: 19_500,
            );

        $this->createItem(
            order: $firstOrder,

            quantity: 2,

            lineTotal: 10_000,
        );

        $this->createItem(
            order: $secondOrder,

            quantity: 3,

            lineTotal: 15_000,
        );

        $this->createItem(
            order: $secondOrder,

            quantity: 1,

            lineTotal: 5_000,
        );

        $range =
            $this->rangeFactory->make(
                preset: ReportDatePreset::Custom,

                from: '2026-11-01',

                to: '2026-11-30',

                timezone: 'Asia/Dhaka',
            );

        $metrics =
            $this->query->get(
                $range,
            );

        $this->assertSame(
            2,
            $metrics->ordersCount,
        );

        $this->assertSame(
            30_000,
            $metrics->grossSales,
        );

        $this->assertSame(
            3_000,
            $metrics->discountTotal,
        );

        $this->assertSame(
            2_000,
            $metrics->shippingTotal,
        );

        $this->assertSame(
            500,
            $metrics->taxTotal,
        );

        $this->assertSame(
            29_500,
            $metrics->netSales,
        );

        $this->assertSame(
            6,
            $metrics->productsSold,
        );

        $this->assertSame(
            1,
            $metrics->customersCount,
        );

        $this->assertSame(
            14_750,
            $metrics->averageOrderValue,
        );
    }

    public function test_cancelled_orders_are_excluded_from_commerce_metrics(): void
    {
        $customer =
            User::factory()
                ->create();

        $activeOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-10 10:00:00',

                paymentStatus: PaymentStatus::Paid,

                subtotal: 10_000,

                discount: 0,

                shipping: 500,

                tax: 0,

                grandTotal: 10_500,
            );

        $cancelledOrder =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-11 10:00:00',

                paymentStatus: PaymentStatus::Paid,

                subtotal: 99_000,

                discount: 0,

                shipping: 1_000,

                tax: 0,

                grandTotal: 100_000,

                status: OrderStatus::Cancelled,
            );

        $this->createItem(
            order: $activeOrder,

            quantity: 2,

            lineTotal: 10_000,
        );

        $this->createItem(
            order: $cancelledOrder,

            quantity: 50,

            lineTotal: 99_000,
        );

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            1,
            $metrics->ordersCount,
        );

        $this->assertSame(
            10_000,
            $metrics->grossSales,
        );

        $this->assertSame(
            10_500,
            $metrics->netSales,
        );

        $this->assertSame(
            2,
            $metrics->productsSold,
        );

        $this->assertSame(
            1,
            $metrics->paidOrdersCount,
        );

        $this->assertSame(
            10_500,
            $metrics->paidTotal,
        );
    }

    public function test_payment_status_totals_are_segmented_authoritatively(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-05 10:00:00',

            paymentStatus: PaymentStatus::Paid,

            subtotal: 10_000,

            grandTotal: 10_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-06 10:00:00',

            paymentStatus: PaymentStatus::Paid,

            subtotal: 20_000,

            grandTotal: 20_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-07 10:00:00',

            paymentStatus: PaymentStatus::Due,

            subtotal: 30_000,

            grandTotal: 30_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-08 10:00:00',

            paymentStatus: PaymentStatus::Unpaid,

            subtotal: 40_000,

            grandTotal: 40_000,
        );

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            2,
            $metrics->paidOrdersCount,
        );

        $this->assertSame(
            30_000,
            $metrics->paidTotal,
        );

        $this->assertSame(
            1,
            $metrics->dueOrdersCount,
        );

        $this->assertSame(
            30_000,
            $metrics->dueTotal,
        );

        $this->assertSame(
            1,
            $metrics->unpaidOrdersCount,
        );

        $this->assertSame(
            40_000,
            $metrics->unpaidTotal,
        );
    }

    public function test_pending_failed_and_refund_states_do_not_fake_paid_due_or_unpaid_totals(): void
    {
        $customer =
            User::factory()
                ->create();

        foreach (
            [
                PaymentStatus::Pending,
                PaymentStatus::Failed,
                PaymentStatus::PartiallyRefunded,
                PaymentStatus::Refunded,
            ] as $index => $paymentStatus
        ) {
            $this->createOrder(
                user: $customer,

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    10 + $index,
                ),

                paymentStatus: $paymentStatus,

                subtotal: 10_000,

                grandTotal: 10_000,
            );
        }

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            4,
            $metrics->ordersCount,
        );

        $this->assertSame(
            0,
            $metrics->paidOrdersCount,
        );

        $this->assertSame(
            0,
            $metrics->paidTotal,
        );

        $this->assertSame(
            0,
            $metrics->dueOrdersCount,
        );

        $this->assertSame(
            0,
            $metrics->dueTotal,
        );

        $this->assertSame(
            0,
            $metrics->unpaidOrdersCount,
        );

        $this->assertSame(
            0,
            $metrics->unpaidTotal,
        );
    }

    public function test_distinct_customers_are_counted_once(): void
    {
        $firstCustomer =
            User::factory()
                ->create();

        $secondCustomer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $firstCustomer,

            placedAt: '2026-11-01 10:00:00',

            grandTotal: 5_000,
        );

        $this->createOrder(
            user: $firstCustomer,

            placedAt: '2026-11-02 10:00:00',

            grandTotal: 5_000,
        );

        $this->createOrder(
            user: $secondCustomer,

            placedAt: '2026-11-03 10:00:00',

            grandTotal: 5_000,
        );

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            3,
            $metrics->ordersCount,
        );

        $this->assertSame(
            2,
            $metrics->customersCount,
        );
    }

    public function test_products_sold_uses_historical_order_item_quantity_even_without_catalog_product(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,

                placedAt: '2026-11-10 10:00:00',

                grandTotal: 10_000,
            );

        OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => null,

                'variant_id' => null,

                'product_name' => 'Deleted historical product',

                'sku' => 'OLD-001',

                'unit_price' => 2_000,

                'quantity' => 5,

                'line_total' => 10_000,
            ]);

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            5,
            $metrics->productsSold,
        );
    }

    public function test_date_range_uses_inclusive_start_and_exclusive_end(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-01 00:00:00',

            grandTotal: 1_000,
        );

        $this->createOrder(
            user: $customer,

            placedAt: '2026-11-30 23:59:59',

            grandTotal: 2_000,
        );

        /*
         * Exactly at the exclusive boundary.
         */
        $this->createOrder(
            user: $customer,

            placedAt: '2026-12-01 00:00:00',

            grandTotal: 99_000,
        );

        /*
         * Before the inclusive boundary.
         */
        $this->createOrder(
            user: $customer,

            placedAt: '2026-10-31 23:59:59',

            grandTotal: 88_000,
        );

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            2,
            $metrics->ordersCount,
        );

        $this->assertSame(
            3_000,
            $metrics->netSales,
        );
    }

    public function test_average_order_value_uses_integer_money_without_float_math(): void
    {
        $customer =
            User::factory()
                ->create();

        foreach (
            [
                10_000,
                10_001,
                10_001,
            ] as $index => $grandTotal
        ) {
            $this->createOrder(
                user: $customer,

                placedAt: sprintf(
                    '2026-11-%02d 12:00:00',
                    $index + 1,
                ),

                grandTotal: $grandTotal,
            );
        }

        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            30_002,
            $metrics->netSales,
        );

        /*
         * Integer minor units only.
         *
         * 30002 / 3 = 10000.666...
         * authoritative integer result = 10000
         */
        $this->assertSame(
            10_000,
            $metrics->averageOrderValue,
        );
    }

    public function test_empty_period_returns_zero_metrics(): void
    {
        $metrics =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            [
                'orders_count' => 0,

                'gross_sales' => 0,

                'discount_total' => 0,

                'shipping_total' => 0,

                'tax_total' => 0,

                'net_sales' => 0,

                'paid_orders_count' => 0,

                'paid_total' => 0,

                'due_orders_count' => 0,

                'due_total' => 0,

                'unpaid_orders_count' => 0,

                'unpaid_total' => 0,

                'products_sold' => 0,

                'customers_count' => 0,

                'average_order_value' => 0,
            ],
            $metrics->toArray(),
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
        User $user,
        string $placedAt,
        PaymentStatus $paymentStatus =
        PaymentStatus::Unpaid,
        int $subtotal = 0,
        int $discount = 0,
        int $shipping = 0,
        int $tax = 0,
        int $grandTotal = 0,
        OrderStatus $status =
        OrderStatus::Processing,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => $status,

                'payment_status' => $paymentStatus,

                'subtotal' => $subtotal,

                'discount_total' => $discount,

                'delivery_total' => $shipping,

                'tax_total' => $tax,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        $unitPrice =
            intdiv(
                $lineTotal,
                $quantity,
            );

        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'unit_price' => $unitPrice,

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
