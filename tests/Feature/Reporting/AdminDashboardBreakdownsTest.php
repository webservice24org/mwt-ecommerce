<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\DashboardBreakdownItemData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardBreakdownsQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardBreakdownsTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardBreakdownsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardBreakdownsQuery::class,
            );
    }

    public function test_payment_breakdown_returns_every_payment_status_with_zero_fill(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 10_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            count(
                PaymentStatus::cases(),
            ),
            $breakdowns->payment,
        );

        $paid =
            $this->findByValue(
                $breakdowns->payment,
                PaymentStatus::Paid->value,
            );

        $unpaid =
            $this->findByValue(
                $breakdowns->payment,
                PaymentStatus::Unpaid->value,
            );

        $this->assertSame(
            1,
            $paid->ordersCount,
        );

        $this->assertSame(
            10_000,
            $paid->total,
        );

        $this->assertSame(
            0,
            $unpaid->ordersCount,
        );

        $this->assertSame(
            0,
            $unpaid->total,
        );
    }

    public function test_payment_breakdown_aggregates_count_and_total_by_status(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 10_000,

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 15_000,

            placedAt: '2026-11-02 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,

            placedAt: '2026-11-03 10:00:00',
        );

        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $paid =
            $this->findByValue(
                $breakdowns->payment,
                'paid',
            );

        $due =
            $this->findByValue(
                $breakdowns->payment,
                'due',
            );

        $this->assertSame(
            2,
            $paid->ordersCount,
        );

        $this->assertSame(
            25_000,
            $paid->total,
        );

        $this->assertSame(
            1,
            $due->ordersCount,
        );

        $this->assertSame(
            20_000,
            $due->total,
        );
    }

    public function test_fulfilment_breakdown_returns_every_status_with_zero_fill(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Processing,

            grandTotal: 5_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertCount(
            count(
                FulfilmentStatus::cases(),
            ),
            $breakdowns->fulfilment,
        );

        $processing =
            $this->findByValue(
                $breakdowns->fulfilment,
                'processing',
            );

        $returned =
            $this->findByValue(
                $breakdowns->fulfilment,
                'returned',
            );

        $this->assertSame(
            1,
            $processing->ordersCount,
        );

        $this->assertSame(
            5_000,
            $processing->total,
        );

        $this->assertSame(
            0,
            $returned->ordersCount,
        );

        $this->assertSame(
            0,
            $returned->total,
        );
    }

    public function test_fulfilment_breakdown_aggregates_orders_by_current_status(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Shipped,

            grandTotal: 10_000,

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Shipped,

            grandTotal: 12_000,

            placedAt: '2026-11-02 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,

            placedAt: '2026-11-03 10:00:00',
        );

        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $shipped =
            $this->findByValue(
                $breakdowns->fulfilment,
                'shipped',
            );

        $delivered =
            $this->findByValue(
                $breakdowns->fulfilment,
                'delivered',
            );

        $this->assertSame(
            2,
            $shipped->ordersCount,
        );

        $this->assertSame(
            22_000,
            $shipped->total,
        );

        $this->assertSame(
            1,
            $delivered->ordersCount,
        );

        $this->assertSame(
            20_000,
            $delivered->total,
        );
    }

    public function test_cancelled_orders_are_excluded_from_both_breakdowns(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 10_000,

            placedAt: '2026-11-05 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 99_000,

            placedAt: '2026-11-06 10:00:00',

            status: OrderStatus::Cancelled,
        );

        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $paid =
            $this->findByValue(
                $breakdowns->payment,
                'paid',
            );

        $delivered =
            $this->findByValue(
                $breakdowns->fulfilment,
                'delivered',
            );

        $this->assertSame(
            1,
            $paid->ordersCount,
        );

        $this->assertSame(
            10_000,
            $paid->total,
        );

        $this->assertSame(
            1,
            $delivered->ordersCount,
        );

        $this->assertSame(
            10_000,
            $delivered->total,
        );
    }

    public function test_orders_outside_selected_date_range_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 10_000,

            placedAt: '2026-10-31 23:59:59',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Processing,

            grandTotal: 20_000,

            placedAt: '2026-11-01 00:00:00',
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

        $breakdowns =
            $this->query->get(
                $range,
            );

        $paid =
            $this->findByValue(
                $breakdowns->payment,
                'paid',
            );

        $due =
            $this->findByValue(
                $breakdowns->payment,
                'due',
            );

        $this->assertSame(
            0,
            $paid->ordersCount,
        );

        $this->assertSame(
            1,
            $due->ordersCount,
        );

        $this->assertSame(
            20_000,
            $due->total,
        );
    }

    public function test_breakdown_array_contract_is_stable(): void
    {
        $breakdowns =
            $this->query->get(
                $this->novemberRange(),
            );

        $payload =
            $breakdowns->toArray();

        $this->assertArrayHasKey(
            'payment',
            $payload,
        );

        $this->assertArrayHasKey(
            'fulfilment',
            $payload,
        );

        $this->assertSame(
            [
                'value',
                'label',
                'orders_count',
                'total',
            ],
            array_keys(
                $payload[
                    'payment'
                ][0],
            ),
        );

        $this->assertSame(
            [
                'value',
                'label',
                'orders_count',
                'total',
            ],
            array_keys(
                $payload[
                    'fulfilment'
                ][0],
            ),
        );
    }

    /**
     * @param  list<DashboardBreakdownItemData>  $items
     */
    private function findByValue(
        array $items,
        string $value,
    ): DashboardBreakdownItemData {
        foreach (
            $items as $item
        ) {
            if (
                $item->value ===
                $value
            ) {
                return $item;
            }
        }

        $this->fail(
            sprintf(
                'Breakdown item "%s" was not found.',
                $value,
            ),
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
        PaymentStatus $paymentStatus,
        FulfilmentStatus $fulfilmentStatus,
        int $grandTotal,
        string $placedAt,
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

                'fulfilment_status' => $fulfilmentStatus,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }
}
