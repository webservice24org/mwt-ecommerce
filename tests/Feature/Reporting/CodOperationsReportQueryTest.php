<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\CodCollectionRiskState;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminCodOperationsReportQuery;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CodOperationsReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_server_constrained_to_cod_orders(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-001',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->codOrdersCount,
        );

        $this->assertCount(
            1,
            $result->rows,
        );

        $this->assertSame(
            PaymentMethod::CashOnDelivery->value,
            $result
                ->rows[0]
                ->paymentMethod,
        );

        $this->assertSame(
            'COD-001',
            $result
                ->rows[0]
                ->orderNumber,
        );
    }

    public function test_summary_reconciles_paid_due_and_unpaid_cod_orders(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-PAID',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 30_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-DUE',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-UNPAID',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $summary =
            $result->summary;

        $this->assertSame(
            3,
            $summary->ordersCount,
        );

        $this->assertSame(
            1,
            $summary->paidOrdersCount,
        );

        $this->assertSame(
            30_000,
            $summary->paidTotal,
        );

        $this->assertSame(
            1,
            $summary->dueOrdersCount,
        );

        $this->assertSame(
            20_000,
            $summary->dueTotal,
        );

        $this->assertSame(
            1,
            $summary->unpaidOrdersCount,
        );

        $this->assertSame(
            10_000,
            $summary->unpaidTotal,
        );
    }

    public function test_delivered_due_and_delivered_unpaid_are_tracked_separately(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-DELIVERED-DUE',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 18_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-DELIVERED-UNPAID',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 12_000,
        );

        $summary =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            )->summary;

        $this->assertSame(
            1,
            $summary
                ->deliveredDueOrdersCount,
        );

        $this->assertSame(
            18_000,
            $summary
                ->deliveredDueTotal,
        );

        $this->assertSame(
            1,
            $summary
                ->deliveredUnpaidOrdersCount,
        );

        $this->assertSame(
            12_000,
            $summary
                ->deliveredUnpaidTotal,
        );
    }

    public function test_paid_cod_order_has_zero_amount_due(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-ZERO-DUE',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 25_000,
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
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

    public function test_due_and_unpaid_orders_expose_full_amount_due(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-DUE-BALANCE',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 14_000,
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            14_000,
            $result
                ->rows[0]
                ->amountDue,
        );
    }

    public function test_filters_and_search_are_applied(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'COD Search Customer',
                ]);

        $this->createOrder(
            customer: $customer,
            number: 'COD-MATCH',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-OTHER',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
        );

        $filters =
            new CodOperationsReportFiltersData(
                range: $this->range(),

                paymentStatuses: [
                    PaymentStatus::Due,
                ],

                fulfilmentStatuses: [
                    FulfilmentStatus::Delivered,
                ],

                search: 'COD-MATCH',
            );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $filters,
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            'COD-MATCH',
            $result
                ->rows[0]
                ->orderNumber,
        );
    }

    public function test_cancelled_cod_orders_are_not_implicitly_excluded(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-CANCELLED',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 8_000,
            status: OrderStatus::Cancelled,
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
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

    public function test_rows_are_ordered_newest_first(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-OLDER',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-NEWER',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-12 10:00:00',
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            'COD-NEWER',
            $result
                ->rows[0]
                ->orderNumber,
        );

        $this->assertSame(
            'COD-OLDER',
            $result
                ->rows[1]
                ->orderNumber,
        );
    }

    private function filters(): CodOperationsReportFiltersData
    {
        return new CodOperationsReportFiltersData(
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
        PaymentMethod $paymentMethod,
        PaymentStatus $paymentStatus,
        FulfilmentStatus $fulfilmentStatus,
        int $grandTotal,
        OrderStatus $status =
        OrderStatus::Processing,
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

                'payment_method' => $paymentMethod,

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

    public function test_rows_expose_collection_age_and_risk_metadata(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        config([
            'reporting.cod_operations.unpaid_stale_hours' => 48,
        ]);

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-RISK-AGED',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-12 10:00:00',
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $row =
            $result->rows[0];

        $this->assertSame(
            74,
            $row->ageHours,
        );

        $this->assertSame(
            3,
            $row->ageDays,
        );

        $this->assertSame(
            CodCollectionRiskState::UnpaidStale,
            $row
                ->collectionRisk
                ->state,
        );

        $this->assertTrue(
            $row
                ->collectionRisk
                ->requiresFollowUp(),
        );

        CarbonImmutable::setTestNow();
    }

    public function test_collection_risk_summary_reconciles_with_rows(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-RISK-DUE',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 15_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-RISK-PAID',
            paymentMethod: PaymentMethod::CashOnDelivery,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
            placedAt: '2026-11-14 10:00:00',
        );

        $result =
            app(
                GetAdminCodOperationsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $followUpRows =
            array_filter(
                $result->rows,
                static fn (
                    $row,
                ): bool => $row
                    ->collectionRisk
                    ->requiresFollowUp(),
            );

        $this->assertSame(
            count(
                $followUpRows,
            ),
            $result
                ->summary
                ->followUpOrdersCount,
        );

        $this->assertSame(
            1,
            $result
                ->summary
                ->criticalRiskOrdersCount,
        );

        CarbonImmutable::setTestNow();
    }
}
