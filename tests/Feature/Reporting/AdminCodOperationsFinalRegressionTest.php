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

final class AdminCodOperationsFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cod_report_or_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.cod',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.operations.cod.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_cod_report_and_csv(): void
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
            ->assertOk();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod.csv',
                ),
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );
    }

    public function test_cod_summary_reconciles_paid_due_and_unpaid_orders(): void
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
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-PAID',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 30_000,
            placedAt: '2026-11-14 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-DUE',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
            placedAt: '2026-11-12 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-UNPAID',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
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
                    ->where(
                        'summary.orders_count',
                        3,
                    )
                    ->where(
                        'summary.cod_orders_count',
                        3,
                    )
                    ->where(
                        'summary.paid_orders_count',
                        1,
                    )
                    ->where(
                        'summary.paid_total',
                        30_000,
                    )
                    ->where(
                        'summary.due_orders_count',
                        1,
                    )
                    ->where(
                        'summary.due_total',
                        20_000,
                    )
                    ->where(
                        'summary.unpaid_orders_count',
                        1,
                    )
                    ->where(
                        'summary.unpaid_total',
                        10_000,
                    )
                    ->where(
                        'summary.delivered_due_orders_count',
                        1,
                    )
                    ->where(
                        'summary.delivered_due_total',
                        20_000,
                    )
                    ->has(
                        'rows',
                        3,
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_collection_risk_summary_reconciles_with_known_rows(): void
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
            number: 'COD-RISK-PAID',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
            placedAt: '2026-11-15 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-RISK-STALE',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-RISK-DELIVERED-DUE',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 15_000,
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
                    )
                    ->where(
                        'rows.0.order_number',
                        'COD-RISK-PAID',
                    )
                    ->where(
                        'rows.0.collection_risk.state',
                        'none',
                    )
                    ->where(
                        'rows.0.collection_risk.requires_follow_up',
                        false,
                    )
                    ->where(
                        'rows.1.order_number',
                        'COD-RISK-DELIVERED-DUE',
                    )
                    ->where(
                        'rows.1.collection_risk.state',
                        'delivered_due',
                    )
                    ->where(
                        'rows.1.collection_risk.severity',
                        'critical',
                    )
                    ->where(
                        'rows.2.order_number',
                        'COD-RISK-STALE',
                    )
                    ->where(
                        'rows.2.collection_risk.state',
                        'unpaid_stale',
                    )
                    ->where(
                        'rows.2.collection_risk.severity',
                        'high',
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_browser_and_csv_reconcile_using_same_filters(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Final COD Customer',
                ]);

        $this->createOrder(
            customer: $customer,
            number: 'COD-FILTER-MATCH',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 15_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'COD-FILTER-OTHER',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 25_000,
        );

        $filters = [
            'status' => [
                OrderStatus::Processing->value,
            ],

            'fulfilment_status' => [
                FulfilmentStatus::Delivered->value,
            ],

            'payment_status' => [
                PaymentStatus::Due->value,
            ],

            'search' => 'COD-FILTER-MATCH',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.cod',
                    $filters,
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
                        'COD-FILTER-MATCH',
                    ),
            );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        $filters,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-FILTER-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'COD-FILTER-OTHER',
            $csv,
        );
    }

    public function test_cancelled_cod_orders_are_not_implicitly_excluded(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-CANCELLED',
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
                    ->where(
                        'rows.0.order_number',
                        'COD-FINAL-CANCELLED',
                    ),
            );
    }

    public function test_paid_order_has_zero_amount_due_in_browser_and_csv(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-PAID-ZERO',
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
                    [
                        'search' => 'COD-FINAL-PAID-ZERO',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'rows.0.amount_due',
                        0,
                    )
                    ->where(
                        'rows.0.collection_risk.state',
                        'none',
                    ),
            );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        [
                            'search' => 'COD-FINAL-PAID-ZERO',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            '300.00',
            $csv,
        );

        $this->assertStringContainsString(
            ',0.00,',
            $csv,
        );

        $this->assertStringContainsString(
            'none',
            $csv,
        );
    }

    public function test_csv_exposes_authoritative_collection_risk_metadata(): void
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

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'COD-FINAL-RISK-CSV',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 12_500,
            placedAt: '2026-11-10 10:00:00',
        );

        $csv =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.reports.operations.cod.csv',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'COD-FINAL-RISK-CSV',
            $csv,
        );

        $this->assertStringContainsString(
            '125.00',
            $csv,
        );

        $this->assertStringContainsString(
            'unpaid_stale',
            $csv,
        );

        $this->assertStringContainsString(
            'high',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );

        CarbonImmutable::setTestNow();
    }

    public function test_cod_operations_frontend_contains_required_workflow_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/CodOperations/Index.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Quick workflows',
            $source,
        );

        $this->assertStringContainsString(
            'Active filters',
            $source,
        );

        $this->assertStringContainsString(
            'Delivered + Due',
            $source,
        );

        $this->assertStringContainsString(
            'Delivered + Unpaid',
            $source,
        );

        $this->assertStringContainsString(
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.cod.csv',
            $source,
        );

        $this->assertStringContainsString(
            'admin.orders.show',
            $source,
        );

        $this->assertStringContainsString(
            'collection_risk.requires_follow_up',
            $source,
        );

        $this->assertStringContainsString(
            'collection_risk.severity',
            $source,
        );

        $this->assertStringContainsString(
            'preserveState',
            $source,
        );

        $this->assertStringContainsString(
            'preserveScroll',
            $source,
        );
    }

    public function test_cod_operations_sidebar_navigation_contract_is_present(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/AdminSidebar.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'COD Operations',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.cod',
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/cod',
            $source,
        );
    }

    public function test_cod_report_and_export_routes_are_registered(): void
    {
        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.cod',
            ),
        );

        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.cod.csv',
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
