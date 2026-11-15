<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\OperationalOrdersReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\OperationalOrderAttentionState;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminOperationalOrdersReportQuery;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalOrdersReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_authoritative_operational_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-001',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-002',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Processing,
            grandTotal: 20_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-003',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 30_000,
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            3,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->pendingOrders,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->processingOrders,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->completedOrders,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->duePaymentOrders,
        );

        $this->assertSame(
            20_000,
            $result
                ->summary
                ->duePaymentTotal,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->unpaidPaymentOrders,
        );

        $this->assertSame(
            10_000,
            $result
                ->summary
                ->unpaidPaymentTotal,
        );
    }

    public function test_paid_orders_have_zero_amount_due(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-PAID',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 25_000,
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            0,
            $result
                ->rows[0]
                ->amountDue,
        );
    }

    public function test_due_and_unpaid_orders_expose_outstanding_total(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-DUE',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 18_000,
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            18_000,
            $result
                ->rows[0]
                ->amountDue,
        );
    }

    public function test_status_and_payment_filters_are_applied(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-MATCH',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Processing,
            grandTotal: 15_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-OTHER',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 30_000,
        );

        $filters =
            new OperationalOrdersReportFiltersData(
                range: $this->range(),

                statuses: [
                    OrderStatus::Processing,
                ],

                paymentStatuses: [
                    PaymentStatus::Due,
                ],
            );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            'OPS-MATCH',
            $result
                ->rows[0]
                ->orderNumber,
        );
    }

    public function test_search_matches_order_number_and_customer_name(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Operations Customer',
                ]);

        $this->createOrder(
            customer: $customer,
            number: 'OPS-SEARCH-001',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
        );

        $filters =
            new OperationalOrdersReportFiltersData(
                range: $this->range(),

                search: 'Operations Customer',
            );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );
    }

    public function test_cancelled_orders_are_not_implicitly_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-CANCELLED',
            status: OrderStatus::Cancelled,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 8_000,
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );
    }

    public function test_rows_are_ordered_by_newest_placed_at_then_id(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-OLDER',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-NEWER',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-12 10:00:00',
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            'OPS-NEWER',
            $result
                ->rows[0]
                ->orderNumber,
        );

        $this->assertSame(
            'OPS-OLDER',
            $result
                ->rows[1]
                ->orderNumber,
        );
    }

    private function filters(): OperationalOrdersReportFiltersData
    {
        return new OperationalOrdersReportFiltersData(
            range: $this->range(),
        );
    }

    private function range(): ReportDateRangeData
    {
        return new ReportDateRangeData(
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
        );
    }

    private function createOrder(
        User $customer,
        string $number,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        FulfilmentStatus $fulfilmentStatus,
        int $grandTotal,
        string $placedAt =
        '2026-11-15 10:00:00',
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => $number,

                'status' => $status,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => $fulfilmentStatus,

                'currency_code' => 'BDT',

                'subtotal' => $grandTotal,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    public function test_rows_expose_ageing_and_attention_metadata(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        config([
            'reporting.operations.pending_age_hours' => 24,
        ]);

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'OPS-AGED',

            status: OrderStatus::Pending,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,

            placedAt: '2026-11-13 10:00:00',
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $row =
            $result->rows[0];

        $this->assertSame(
            50,
            $row->ageHours,
        );

        $this->assertSame(
            2,
            $row->ageDays,
        );

        $this->assertSame(
            OperationalOrderAttentionState::PendingTooLong,
            $row
                ->attention
                ->state,
        );

        $this->assertTrue(
            $row
                ->attention
                ->requiresAttention(),
        );

        CarbonImmutable::setTestNow();
    }

    public function test_attention_summary_reconciles_with_row_classification(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        config([
            'reporting.operations.pending_age_hours' => 24,
        ]);

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'OPS-ATTENTION',

            status: OrderStatus::Pending,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 8_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,

            number: 'OPS-NORMAL',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,

            placedAt: '2026-11-15 10:00:00',
        );

        $result =
            app(
                GetAdminOperationalOrdersReportQuery::class,
            )->get(
                $this->filters(),
            );

        $attentionRows =
            array_filter(
                $result->rows,
                static fn (
                    $row,
                ): bool => $row
                    ->attention
                    ->requiresAttention(),
            );

        $this->assertSame(
            count(
                $attentionRows,
            ),
            $result
                ->summary
                ->attentionRequiredOrders,
        );

        CarbonImmutable::setTestNow();
    }
}
