<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Charts\BuildSalesAnalyticsCharts;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminSalesAnalyticsReportQuery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SalesAnalyticsReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_authoritative_sales_analytics_summary(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Analytics Customer',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'ANALYTICS-001',
                placedAt: '2026-11-10 10:00:00',
                subtotal: 10_000,
                discountTotal: 1_000,
                deliveryTotal: 800,
                taxTotal: 200,
                grandTotal: 10_000,
                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $firstOrder,
            quantity: 2,
            lineTotal: 10_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'ANALYTICS-002',
                placedAt: '2026-11-11 10:00:00',
                subtotal: 20_000,
                discountTotal: 2_000,
                deliveryTotal: 1_200,
                taxTotal: 300,
                grandTotal: 19_500,
                paymentStatus: PaymentStatus::Due,
            );

        $this->createItem(
            order: $secondOrder,
            quantity: 3,
            lineTotal: 20_000,
        );

        $result =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            2,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            5,
            $result
                ->summary
                ->itemsQuantity,
        );

        $this->assertSame(
            30_000,
            $result
                ->summary
                ->subtotal,
        );

        $this->assertSame(
            3_000,
            $result
                ->summary
                ->discountTotal,
        );

        $this->assertSame(
            2_000,
            $result
                ->summary
                ->deliveryTotal,
        );

        $this->assertSame(
            500,
            $result
                ->summary
                ->taxTotal,
        );

        $this->assertSame(
            29_500,
            $result
                ->summary
                ->grandTotal,
        );

        $this->assertSame(
            10_000,
            $result
                ->summary
                ->paidRevenue,
        );

        $this->assertSame(
            14_750,
            $result
                ->summary
                ->averageOrderValue,
        );
    }

    public function test_trend_is_grouped_by_day_in_ascending_order(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,
            orderNumber: 'TREND-002',
            placedAt: '2026-11-12 10:00:00',
            subtotal: 20_000,
            grandTotal: 20_000,
            paymentStatus: PaymentStatus::Paid,
        );

        $this->createOrder(
            user: $customer,
            orderNumber: 'TREND-001',
            placedAt: '2026-11-10 10:00:00',
            subtotal: 10_000,
            grandTotal: 10_000,
            paymentStatus: PaymentStatus::Paid,
        );

        $result =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
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
    }

    public function test_cancelled_orders_are_not_implicitly_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,
            orderNumber: 'CANCELLED-ANALYTICS',
            placedAt: '2026-11-10 10:00:00',
            subtotal: 9_000,
            grandTotal: 9_000,
            status: OrderStatus::Cancelled,
            paymentStatus: PaymentStatus::Unpaid,
        );

        $result =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
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
            OrderStatus::Cancelled->value,
            $result
                ->statusBreakdown[0]
                ->key,
        );
    }

    public function test_search_filter_matches_existing_sales_report_semantics(): void
    {
        $matchingCustomer =
            User::factory()
                ->create([
                    'name' => 'Special Customer',
                ]);

        $otherCustomer =
            User::factory()
                ->create([
                    'name' => 'Other Customer',
                ]);

        $this->createOrder(
            user: $matchingCustomer,
            orderNumber: 'MATCH-001',
            placedAt: '2026-11-10 10:00:00',
            subtotal: 10_000,
            grandTotal: 10_000,
            paymentStatus: PaymentStatus::Paid,
        );

        $this->createOrder(
            user: $otherCustomer,
            orderNumber: 'OTHER-001',
            placedAt: '2026-11-10 10:00:00',
            subtotal: 20_000,
            grandTotal: 20_000,
            paymentStatus: PaymentStatus::Paid,
        );

        $result =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $this->filters(
                    search: 'Special',
                ),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            10_000,
            $result
                ->summary
                ->grandTotal,
        );
    }

    private function filters(
        ?string $search = null,
    ): SalesReportFiltersData {
        return new SalesReportFiltersData(
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
        int $subtotal,
        int $grandTotal,
        PaymentStatus $paymentStatus,
        OrderStatus $status =
        OrderStatus::Completed,
        int $discountTotal = 0,
        int $deliveryTotal = 0,
        int $taxTotal = 0,
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

                'subtotal' => $subtotal,

                'discount_total' => $discountTotal,

                'delivery_total' => $deliveryTotal,

                'tax_total' => $taxTotal,

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
                'product_name' => 'Analytics Product',

                'sku' => 'ANALYTICS-SKU',

                'unit_price' => $unitPrice,

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }

    public function test_chart_datasets_reconcile_with_authoritative_analytics_result(): void
    {
        $customer =
            User::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'CHART-RECONCILE-001',
                placedAt: '2026-11-15 10:00:00',
                subtotal: 12_000,
                grandTotal: 12_000,
                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $order,
            quantity: 2,
            lineTotal: 12_000,
        );

        $report =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $charts =
            app(
                BuildSalesAnalyticsCharts::class,
            )->build(
                $report,
            );

        $this->assertSame(
            $report
                ->summary
                ->grandTotal,
            $charts
                ->revenueTrend
                ->points[0]
                ->values[
                'grand_total'
            ],
        );

        $this->assertSame(
            $report
                ->summary
                ->ordersCount,
            $charts
                ->ordersTrend
                ->points[0]
                ->values[
                'orders_count'
            ],
        );
    }
}
