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

final class AdminOperationalOrdersFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_operational_orders_report_or_csv(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.orders',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.operations.orders.csv',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_operational_orders_report_and_csv(): void
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
                    'admin.reports.operations.orders',
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
                    'admin.reports.operations.orders.csv',
                ),
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );
    }

    public function test_browser_summary_reconciles_with_matching_operational_orders(): void
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
            number: 'OPS-REG-001',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-REG-002',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Processing,
            grandTotal: 20_000,
            placedAt: '2026-11-12 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-REG-003',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 30_000,
            placedAt: '2026-11-14 10:00:00',
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.orders',
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
                        'summary.pending_orders',
                        1,
                    )
                    ->where(
                        'summary.processing_orders',
                        1,
                    )
                    ->where(
                        'summary.completed_orders',
                        1,
                    )
                    ->where(
                        'summary.unfulfilled_orders',
                        1,
                    )
                    ->where(
                        'summary.fulfilment_processing_orders',
                        1,
                    )
                    ->where(
                        'summary.delivered_orders',
                        1,
                    )
                    ->where(
                        'summary.due_payment_orders',
                        1,
                    )
                    ->where(
                        'summary.due_payment_total',
                        20_000,
                    )
                    ->where(
                        'summary.unpaid_payment_orders',
                        1,
                    )
                    ->where(
                        'summary.unpaid_payment_total',
                        10_000,
                    )
                    ->has(
                        'rows',
                        3,
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_attention_summary_reconciles_with_returned_rows(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-15 12:00:00',
                'Asia/Dhaka',
            ),
        );

        config([
            'reporting.operations.pending_age_hours' => 24,

            'reporting.operations.processing_age_hours' => 48,

            'reporting.operations.unfulfilled_age_hours' => 48,
        ]);

        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-ATTN-001',
            status: OrderStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            fulfilmentStatus: FulfilmentStatus::Unfulfilled,
            grandTotal: 10_000,
            placedAt: '2026-11-10 10:00:00',
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-ATTN-002',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 20_000,
            placedAt: '2026-11-15 10:00:00',
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.orders',
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
                        'summary.attention_required_orders',
                        1,
                    )
                    ->where(
                        'summary.critical_attention_orders',
                        0,
                    )
                    ->where(
                        'rows.0.order_number',
                        'OPS-ATTN-002',
                    )
                    ->where(
                        'rows.0.attention.requires_attention',
                        false,
                    )
                    ->where(
                        'rows.0.attention.state',
                        'none',
                    )
                    ->where(
                        'rows.1.order_number',
                        'OPS-ATTN-001',
                    )
                    ->where(
                        'rows.1.attention.requires_attention',
                        true,
                    )
                    ->where(
                        'rows.1.attention.state',
                        'pending_too_long',
                    )
                    ->where(
                        'rows.1.attention.severity',
                        'warning',
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_cancelled_orders_are_not_implicitly_excluded(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            customer: $customer,
            number: 'OPS-CANCELLED-FINAL',
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
                    'admin.reports.operations.orders',
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
                        'OPS-CANCELLED-FINAL',
                    ),
            );
    }

    public function test_operational_filters_reconcile_between_browser_and_csv(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Final Operations Customer',
                ]);

        $this->createOrder(
            customer: $customer,
            number: 'OPS-FINAL-MATCH',
            status: OrderStatus::Processing,
            paymentStatus: PaymentStatus::Due,
            fulfilmentStatus: FulfilmentStatus::Processing,
            grandTotal: 15_000,
        );

        $this->createOrder(
            customer: $customer,
            number: 'OPS-FINAL-OTHER',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            fulfilmentStatus: FulfilmentStatus::Delivered,
            grandTotal: 25_000,
        );

        $query = [
            'status' => [
                OrderStatus::Processing->value,
            ],

            'payment_status' => [
                PaymentStatus::Due->value,
            ],

            'search' => 'OPS-FINAL-MATCH',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.orders',
                    $query,
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
                        'OPS-FINAL-MATCH',
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
                        'admin.reports.operations.orders.csv',
                        $query,
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'OPS-FINAL-MATCH',
            $csv,
        );

        $this->assertStringNotContainsString(
            'OPS-FINAL-OTHER',
            $csv,
        );
    }

    public function test_csv_contains_authoritative_operational_attention_and_money_fields(): void
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
            number: 'OPS-FINAL-CSV',
            status: OrderStatus::Pending,
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
                        'admin.reports.operations.orders.csv',
                        [
                            'preset' => 'custom',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',
                        ],
                    ),
                )
                ->streamedContent();

        $this->assertStringContainsString(
            'OPS-FINAL-CSV',
            $csv,
        );

        $this->assertStringContainsString(
            ',12500,12500,BDT,',
            $csv,
        );

        $this->assertStringContainsString(
            'pending_too_long',
            $csv,
        );

        $this->assertStringContainsString(
            'warning',
            $csv,
        );

        $this->assertStringContainsString(
            'Yes',
            $csv,
        );

        CarbonImmutable::setTestNow();
    }

    public function test_operational_orders_frontend_contains_required_workflow_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/OperationalOrders/Index.tsx',
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
            'Export CSV',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.orders.csv',
            $source,
        );

        $this->assertStringContainsString(
            'admin.orders.show',
            $source,
        );

        $this->assertStringContainsString(
            'attention.requires_attention',
            $source,
        );

        $this->assertStringContainsString(
            'attention.severity',
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

    public function test_operational_orders_sidebar_navigation_contract_is_present(): void
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
            'Operational Orders',
            $source,
        );

        $this->assertStringContainsString(
            'admin.reports.operations.orders',
            $source,
        );

        $this->assertStringContainsString(
            '/admin/reports/operations/orders',
            $source,
        );
    }

    public function test_report_and_export_routes_are_registered(): void
    {
        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.orders',
            ),
        );

        $this->assertTrue(
            app(
                'router',
            )->has(
                'admin.reports.operations.orders.csv',
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
}
