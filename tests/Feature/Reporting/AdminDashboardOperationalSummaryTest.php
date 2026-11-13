<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminDashboardOperationalSummaryQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardOperationalSummaryTest extends TestCase
{
    use RefreshDatabase;

    private ReportDateRangeFactory $rangeFactory;

    private GetAdminDashboardOperationalSummaryQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeFactory =
            app(
                ReportDateRangeFactory::class,
            );

        $this->query =
            app(
                GetAdminDashboardOperationalSummaryQuery::class,
            );
    }

    public function test_operational_summary_counts_order_statuses(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Pending,

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Processing,

            placedAt: '2026-11-02 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Completed,

            placedAt: '2026-11-03 10:00:00',
        );

        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            1,
            $summary->pendingOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->processingOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->completedOrdersCount,
        );
    }

    public function test_operational_summary_counts_fulfilment_states(): void
    {
        $customer =
            User::factory()
                ->create();

        foreach (
            [
                FulfilmentStatus::Unfulfilled,
                FulfilmentStatus::Processing,
                FulfilmentStatus::Shipped,
                FulfilmentStatus::Delivered,
                FulfilmentStatus::Returned,
            ] as $index => $status
        ) {
            $this->createOrder(
                user: $customer,

                fulfilmentStatus: $status,

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    $index + 1,
                ),
            );
        }

        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            1,
            $summary->unfulfilledOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->fulfilmentProcessingOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->shippedOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->deliveredOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->returnedOrdersCount,
        );
    }

    public function test_due_and_unpaid_payment_queues_include_counts_and_totals(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Due,

            grandTotal: 10_000,

            placedAt: '2026-11-01 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Due,

            grandTotal: 15_000,

            placedAt: '2026-11-02 10:00:00',
        );

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Unpaid,

            grandTotal: 20_000,

            placedAt: '2026-11-03 10:00:00',
        );

        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            2,
            $summary->duePaymentOrdersCount,
        );

        $this->assertSame(
            25_000,
            $summary->duePaymentTotal,
        );

        $this->assertSame(
            1,
            $summary->unpaidPaymentOrdersCount,
        );

        $this->assertSame(
            20_000,
            $summary->unpaidPaymentTotal,
        );
    }

    public function test_paid_orders_do_not_appear_in_due_or_unpaid_queues(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            paymentStatus: PaymentStatus::Paid,

            grandTotal: 50_000,

            placedAt: '2026-11-05 10:00:00',
        );

        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            0,
            $summary->duePaymentOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->duePaymentTotal,
        );

        $this->assertSame(
            0,
            $summary->unpaidPaymentOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->unpaidPaymentTotal,
        );
    }

    public function test_cancelled_orders_are_excluded_from_operational_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Cancelled,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Shipped,

            grandTotal: 99_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            0,
            $summary->pendingOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->processingOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->completedOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->shippedOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->duePaymentOrdersCount,
        );

        $this->assertSame(
            0,
            $summary->duePaymentTotal,
        );
    }

    public function test_orders_outside_selected_range_are_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Pending,

            placedAt: '2026-10-31 23:59:59',
        );

        $this->createOrder(
            user: $customer,

            status: OrderStatus::Processing,

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

        $summary =
            $this->query->get(
                $range,
            );

        $this->assertSame(
            0,
            $summary->pendingOrdersCount,
        );

        $this->assertSame(
            1,
            $summary->processingOrdersCount,
        );
    }

    public function test_empty_period_returns_zero_operational_summary(): void
    {
        $summary =
            $this->query->get(
                $this->novemberRange(),
            );

        $this->assertSame(
            [
                'pending_orders_count' => 0,

                'processing_orders_count' => 0,

                'completed_orders_count' => 0,

                'unfulfilled_orders_count' => 0,

                'fulfilment_processing_orders_count' => 0,

                'shipped_orders_count' => 0,

                'delivered_orders_count' => 0,

                'returned_orders_count' => 0,

                'due_payment_orders_count' => 0,

                'due_payment_total' => 0,

                'unpaid_payment_orders_count' => 0,

                'unpaid_payment_total' => 0,
            ],
            $summary->toArray(),
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
        OrderStatus $status =
        OrderStatus::Processing,
        PaymentStatus $paymentStatus =
        PaymentStatus::Unpaid,
        FulfilmentStatus $fulfilmentStatus =
        FulfilmentStatus::Processing,
        int $grandTotal = 0,
        string $placedAt =
        '2026-11-01 10:00:00',
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
