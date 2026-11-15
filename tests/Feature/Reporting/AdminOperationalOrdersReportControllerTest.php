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

final class AdminOperationalOrdersReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_operational_orders_report(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.operations.orders',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_view_operational_orders_report(): void
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
                    'name' => 'Operations Customer',
                ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'OPS-WEB-001',

                'status' => OrderStatus::Pending,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,

                'currency_code' => 'BDT',

                'subtotal' => 20_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 20_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 10:00:00',
                    'Asia/Dhaka',
                ),
            ]);

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
                    ->component(
                        'Admin/Reports/OperationalOrders/Index',
                    )
                    ->where(
                        'summary.orders_count',
                        1,
                    )
                    ->where(
                        'rows.0.order_number',
                        'OPS-WEB-001',
                    )
                    ->where(
                        'rows.0.customer_name',
                        'Operations Customer',
                    )
                    ->where(
                        'rows.0.attention.requires_attention',
                        true,
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'pagination',
                    )
                    ->has(
                        'options.statuses',
                    )
                    ->has(
                        'options.fulfilment_statuses',
                    )
                    ->has(
                        'options.payment_statuses',
                    )
                    ->has(
                        'options.payment_methods',
                    ),
            );

        CarbonImmutable::setTestNow();
    }

    public function test_filters_are_applied_to_operational_report(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'OPS-FILTER-001',

                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Due,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'currency_code' => 'BDT',

                'subtotal' => 10_000,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 10_000,

                'placed_at' => now(),
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.operations.orders',
                    [
                        'status' => [
                            OrderStatus::Processing->value,
                        ],

                        'payment_status' => [
                            PaymentStatus::Due->value,
                        ],

                        'search' => 'OPS-FILTER-001',
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
                        'rows.0.order_number',
                        'OPS-FILTER-001',
                    ),
            );
    }
}
