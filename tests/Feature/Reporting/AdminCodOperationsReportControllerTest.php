<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminCodOperationsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cod_operations_report(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.cod',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_cod_operations_report(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Reports/CodOperations/Index',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'summary',
                    )
                    ->has(
                        'rows',
                    )
                    ->has(
                        'pagination',
                    )
                    ->has(
                        'options',
                    ),
            );
    }

    public function test_cod_operations_report_returns_summary_rows_and_options(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'COD Operations Customer',
                ]);

        $this->createOrder(
            customer: $customer,

            number: 'COD-REPORT-001',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 25_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Reports/CodOperations/Index',
                    )
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->where(
                        'summary.cod_orders_count',
                        1,
                    )
                    ->where(
                        'summary.due_orders_count',
                        1,
                    )
                    ->where(
                        'summary.due_total',
                        25_000,
                    )
                    ->where(
                        'summary.delivered_due_orders_count',
                        1,
                    )
                    ->where(
                        'summary.delivered_due_total',
                        25_000,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-REPORT-001',
                    )
                    ->where(
                        'rows.0.customer_name',
                        'COD Operations Customer',
                    )
                    ->where(
                        'rows.0.payment_method',
                        PaymentMethod::CashOnDelivery->value,
                    )
                    ->where(
                        'rows.0.payment_status',
                        PaymentStatus::Due->value,
                    )
                    ->where(
                        'rows.0.fulfilment_status',
                        FulfilmentStatus::Delivered->value,
                    )
                    ->where(
                        'rows.0.grand_total',
                        25_000,
                    )
                    ->where(
                        'rows.0.amount_due',
                        25_000,
                    )
                    ->where(
                        'rows.0.collection_risk.requires_follow_up',
                        true,
                    )
                    ->has(
                        'options.statuses',
                    )
                    ->has(
                        'options.fulfilment_statuses',
                    )
                    ->has(
                        'options.payment_statuses',
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_filters_are_preserved_in_inertia_props(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Filter Search Customer',
                ]);

        $this->createOrder(
            customer: $customer,

            number: 'COD-FILTER-001',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 15_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'status' => [
                            OrderStatus::Processing->value,
                        ],

                        'fulfilment_status' => [
                            FulfilmentStatus::Delivered->value,
                        ],

                        'payment_status' => [
                            PaymentStatus::Due->value,
                        ],

                        'search' => 'COD-FILTER-001',

                        'per_page' => 50,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'filters.preset',
                        'custom',
                    )
                    ->where(
                        'filters.from',
                        '2026-11-01',
                    )
                    ->where(
                        'filters.to',
                        '2026-11-30',
                    )
                    ->where(
                        'filters.statuses',
                        [
                            OrderStatus::Processing->value,
                        ],
                    )
                    ->where(
                        'filters.fulfilment_statuses',
                        [
                            FulfilmentStatus::Delivered->value,
                        ],
                    )
                    ->where(
                        'filters.payment_statuses',
                        [
                            PaymentStatus::Due->value,
                        ],
                    )
                    ->where(
                        'filters.search',
                        'COD-FILTER-001',
                    )
                    ->where(
                        'filters.per_page',
                        50,
                    )
                    ->where(
                        'pagination.per_page',
                        50,
                    ),
            );
    }

    public function test_report_respects_payment_status_filter(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-DUE-MATCH',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-PAID-OTHER',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'payment_status' => [
                            PaymentStatus::Due->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->where(
                        'summary.due_orders_count',
                        1,
                    )
                    ->where(
                        'summary.paid_orders_count',
                        0,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-DUE-MATCH',
                    ),
            );
    }

    public function test_report_respects_order_and_fulfilment_filters(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-FULFILMENT-MATCH',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Shipped,

            grandTotal: 12_000,
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-FULFILMENT-OTHER',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 18_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'status' => [
                            OrderStatus::Processing->value,
                        ],

                        'fulfilment_status' => [
                            FulfilmentStatus::Shipped->value,
                        ],
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-FULFILMENT-MATCH',
                    )
                    ->where(
                        'rows.0.status',
                        OrderStatus::Processing->value,
                    )
                    ->where(
                        'rows.0.fulfilment_status',
                        FulfilmentStatus::Shipped->value,
                    ),
            );
    }

    public function test_report_searches_by_order_number(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-SEARCH-MATCH',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-SEARCH-OTHER',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'search' => 'COD-SEARCH-MATCH',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-SEARCH-MATCH',
                    ),
            );
    }

    public function test_report_searches_by_customer_name(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $matchingCustomer =
            User::factory()
                ->create([
                    'name' => 'Special COD Customer',
                ]);

        $otherCustomer =
            User::factory()
                ->create([
                    'name' => 'Another Customer',
                ]);

        $this->createOrder(
            customer: $matchingCustomer,

            number: 'COD-CUSTOMER-MATCH',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $this->createOrder(
            customer: $otherCustomer,

            number: 'COD-CUSTOMER-OTHER',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'search' => 'Special COD Customer',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-CUSTOMER-MATCH',
                    )
                    ->where(
                        'rows.0.customer_name',
                        'Special COD Customer',
                    ),
            );
    }

    public function test_report_exposes_collection_risk_summary(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        config([
            'reporting.cod_operations.unpaid_stale_hours' => 48,

            'reporting.cod_operations.due_grace_hours' => 24,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-HIGH-RISK',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 10_000,

            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,

            number: 'COD-CRITICAL-RISK',

            status: OrderStatus::Processing,

            paymentStatus: PaymentStatus::Due,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 20_000,

            placedAt: '2026-11-12 10:00:00',
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        2,
                    )
                    ->where(
                        'summary.follow_up_orders_count',
                        2,
                    )
                    ->where(
                        'summary.high_risk_orders_count',
                        1,
                    )
                    ->where(
                        'summary.critical_risk_orders_count',
                        1,
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_paid_order_exposes_zero_amount_due(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-PAID-ZERO',

            status: OrderStatus::Completed,

            paymentStatus: PaymentStatus::Paid,

            fulfilmentStatus: FulfilmentStatus::Delivered,

            grandTotal: 30_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'rows.0.order_number',
                        'COD-PAID-ZERO',
                    )
                    ->where(
                        'rows.0.grand_total',
                        30_000,
                    )
                    ->where(
                        'rows.0.amount_due',
                        0,
                    )
                    ->where(
                        'rows.0.collection_risk.state',
                        'none',
                    )
                    ->where(
                        'rows.0.collection_risk.requires_follow_up',
                        false,
                    ),
            );
    }

    public function test_cancelled_cod_order_is_not_implicitly_excluded(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,

            number: 'COD-CANCELLED-REPORT',

            status: OrderStatus::Cancelled,

            paymentStatus: PaymentStatus::Unpaid,

            fulfilmentStatus: FulfilmentStatus::Unfulfilled,

            grandTotal: 8_000,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->has(
                        'rows',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-CANCELLED-REPORT',
                    )
                    ->where(
                        'rows.0.status',
                        OrderStatus::Cancelled->value,
                    ),
            );
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'status' => [
                            'invalid-status',
                        ],

                        'payment_status' => [
                            'invalid-payment-status',
                        ],

                        'per_page' => 999,
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'status.0',
                'payment_status.0',
                'per_page',
            ]);
    }

    public function test_custom_date_range_requires_from_and_to_dates(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'preset' => 'custom',
                    ],
                ),
            )
            ->assertSessionHasErrors([
                'from',
                'to',
            ]);
    }

    public function test_pagination_uses_requested_per_page_value(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $this->createOrder(
                customer: $customer,

                number: sprintf(
                    'COD-PAGE-%03d',
                    $index,
                ),

                status: OrderStatus::Processing,

                paymentStatus: PaymentStatus::Unpaid,

                fulfilmentStatus: FulfilmentStatus::Unfulfilled,

                grandTotal: 10_000,

                placedAt: sprintf(
                    '2026-11-%02d 10:00:00',
                    min(
                        $index,
                        15,
                    ),
                ),
            );
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    [
                        'per_page' => 10,

                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'pagination.current_page',
                        1,
                    )
                    ->where(
                        'pagination.per_page',
                        10,
                    )
                    ->where(
                        'pagination.total',
                        12,
                    )
                    ->where(
                        'pagination.last_page',
                        2,
                    )
                    ->has(
                        'rows',
                        10,
                    ),
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
        ?string $paymentDueAt =
        null,
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

                'payment_due_at' => $paymentDueAt !== null
                    ? CarbonImmutable::parse(
                        $paymentDueAt,
                        'Asia/Dhaka',
                    )
                    : null,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }
}
