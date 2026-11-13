<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardRecentOrdersQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardRecentOrdersTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardRecentOrdersQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardRecentOrdersQuery::class,
            );
    }

    public function test_recent_orders_returns_newest_non_cancelled_orders_first(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Dashboard Customer',
                ]);

        $older =
            $this->createOrder(
                user: $customer,

                orderNumber: 'ORD-OLD',

                placedAt: '2026-11-01 10:00:00',

                grandTotal: 10_000,
            );

        $newer =
            $this->createOrder(
                user: $customer,

                orderNumber: 'ORD-NEW',

                placedAt: '2026-11-10 15:00:00',

                grandTotal: 20_000,
            );

        $orders =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            2,
            $orders,
        );

        $this->assertSame(
            $newer->id,
            $orders[0]->id,
        );

        $this->assertSame(
            'ORD-NEW',
            $orders[0]->orderNumber,
        );

        $this->assertSame(
            $older->id,
            $orders[1]->id,
        );
    }

    public function test_recent_orders_are_limited(): void
    {
        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 10;
            $index++
        ) {
            $this->createOrder(
                user: $customer,

                orderNumber: sprintf(
                    'ORD-%02d',
                    $index,
                ),

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    $index,
                ),

                grandTotal: 1_000 * $index,
            );
        }

        $orders =
            $this->query->get(
                range: $this->novemberRange(),

                limit: 5,
            );

        $this->assertCount(
            5,
            $orders,
        );

        $this->assertSame(
            'ORD-10',
            $orders[0]->orderNumber,
        );

        $this->assertSame(
            'ORD-06',
            $orders[4]->orderNumber,
        );
    }

    public function test_limit_is_capped_at_twenty(): void
    {
        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 25;
            $index++
        ) {
            $day =
                (($index - 1) % 25)
                + 1;

            $this->createOrder(
                user: $customer,

                orderNumber: sprintf(
                    'ORD-%02d',
                    $index,
                ),

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    $day,
                ),

                grandTotal: 1_000,
            );
        }

        $orders =
            $this->query->get(
                range: $this->novemberRange(),

                limit: 999,
            );

        $this->assertCount(
            20,
            $orders,
        );
    }

    public function test_cancelled_orders_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-ACTIVE',

            placedAt: '2026-11-10 10:00:00',

            grandTotal: 10_000,
        );

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-CANCELLED',

            placedAt: '2026-11-11 10:00:00',

            grandTotal: 99_000,

            status: OrderStatus::Cancelled,
        );

        $orders =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            1,
            $orders,
        );

        $this->assertSame(
            'ORD-ACTIVE',
            $orders[0]->orderNumber,
        );
    }

    public function test_orders_outside_selected_range_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-OCTOBER',

            placedAt: '2026-10-31 23:59:59',

            grandTotal: 10_000,
        );

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-NOVEMBER',

            placedAt: '2026-11-01 00:00:00',

            grandTotal: 20_000,
        );

        $orders =
            $this->query->get(
                range: $this->rangeFactory
                    ->make(
                        preset: ReportDatePreset::Custom,

                        from: '2026-11-01',

                        to: '2026-11-01',

                        timezone: 'Asia/Dhaka',
                    ),
            );

        $this->assertCount(
            1,
            $orders,
        );

        $this->assertSame(
            'ORD-NOVEMBER',
            $orders[0]->orderNumber,
        );
    }

    public function test_recent_order_exposes_status_labels_and_customer_name(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Mohiuddin Customer',
                ]);

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-STATUS',

            placedAt: '2026-11-10 10:00:00',

            grandTotal: 15_000,

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Shipped,
        );

        $order =
            $this
                ->query
                ->get(
                    $this->novemberRange(),
                )[0];

        $this->assertSame(
            'Mohiuddin Customer',
            $order->customerName,
        );

        $this->assertSame(
            'processing',
            $order->status,
        );

        $this->assertSame(
            'Processing',
            $order->statusLabel,
        );

        $this->assertSame(
            'due',
            $order->paymentStatus,
        );

        $this->assertSame(
            'Due',
            $order->paymentStatusLabel,
        );

        $this->assertSame(
            'shipped',
            $order->fulfilmentStatus,
        );

        $this->assertSame(
            'Shipped',
            $order->fulfilmentStatusLabel,
        );
    }

    public function test_recent_order_preserves_integer_money(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            orderNumber: 'ORD-MONEY',

            placedAt: '2026-11-10 10:00:00',

            grandTotal: 123_456,
        );

        $order =
            $this
                ->query
                ->get(
                    $this->novemberRange(),
                )[0];

        $this->assertSame(
            123_456,
            $order->grandTotal,
        );
    }

    public function test_recent_order_array_contract_is_stable(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Test Customer',
                ]);

        $created =
            $this->createOrder(
                user: $customer,

                orderNumber: 'ORD-CONTRACT',

                placedAt: '2026-11-10 10:00:00',

                grandTotal: 25_000,

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Paid,

                fulfilmentStatus: FulfilmentStatus::Delivered,
            );

        $order =
            $this
                ->query
                ->get(
                    $this->novemberRange(),
                )[0];

        $payload =
            $order->toArray();

        $this->assertSame(
            $created->id,
            $payload['id'],
        );

        $this->assertSame(
            'ORD-CONTRACT',
            $payload['order_number'],
        );

        $this->assertSame(
            'Test Customer',
            $payload['customer_name'],
        );

        $this->assertSame(
            'processing',
            $payload['status'],
        );

        $this->assertSame(
            'Processing',
            $payload['status_label'],
        );

        $this->assertSame(
            'paid',
            $payload['payment_status'],
        );

        $this->assertSame(
            'Paid',
            $payload[
                'payment_status_label'
            ],
        );

        $this->assertSame(
            'delivered',
            $payload[
                'fulfilment_status'
            ],
        );

        $this->assertSame(
            'Delivered',
            $payload[
                'fulfilment_status_label'
            ],
        );

        $this->assertSame(
            'BDT',
            $payload['currency_code'],
        );

        $this->assertSame(
            25_000,
            $payload['grand_total'],
        );

        $this->assertIsString(
            $payload['placed_at'],
        );
    }

    public function test_same_timestamp_is_ordered_by_newest_order_id(): void
    {
        $customer =
            User::factory()
                ->create();

        $first =
            $this->createOrder(
                user: $customer,

                orderNumber: 'ORD-FIRST',

                placedAt: '2026-11-10 10:00:00',

                grandTotal: 10_000,
            );

        $second =
            $this->createOrder(
                user: $customer,

                orderNumber: 'ORD-SECOND',

                placedAt: '2026-11-10 10:00:00',

                grandTotal: 20_000,
            );

        $orders =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            $second->id,
            $orders[0]->id,
        );

        $this->assertSame(
            $first->id,
            $orders[1]->id,
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
        string $orderNumber,
        string $placedAt,
        int $grandTotal,
        OrderStatus $status =
        OrderStatus::Processing,
        PaymentStatus $paymentStatus =
        PaymentStatus::Unpaid,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Processing,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'order_number' => $orderNumber,

                'status' => $status,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => $fulfilmentStatus,

                'currency_code' => 'BDT',

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }
}
