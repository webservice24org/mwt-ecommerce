<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminSalesReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_requires_admin_authentication(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_sales_report_renders_expected_inertia_component(): void
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
                    'admin.reports.sales.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Reports/Sales/Index',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'query',
                    )
                    ->has(
                        'options',
                    )
                    ->has(
                        'report',
                    ),
            );
    }

    public function test_sales_report_exposes_default_filter_contract(): void
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
                    'admin.reports.sales.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'filters.range.preset',
                        'this_month',
                    )
                    ->where(
                        'filters.statuses',
                        [],
                    )
                    ->where(
                        'filters.payment_statuses',
                        [],
                    )
                    ->where(
                        'filters.search',
                        null,
                    )
                    ->where(
                        'filters.per_page',
                        25,
                    )
                    ->where(
                        'query.preset',
                        'this_month',
                    )
                    ->where(
                        'query.per_page',
                        25,
                    ),
            );
    }

    public function test_sales_report_exposes_filter_options(): void
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
                    'admin.reports.sales.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'options.presets',
                        6,
                    )
                    ->has(
                        'options.statuses',
                        count(
                            OrderStatus::cases(),
                        ),
                    )
                    ->has(
                        'options.payment_statuses',
                        count(
                            PaymentStatus::cases(),
                        ),
                    )
                    ->where(
                        'options.per_page',
                        [
                            10,
                            25,
                            50,
                            100,
                        ],
                    ),
            );
    }

    public function test_sales_report_applies_custom_filters_to_query_and_result(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create([
                    'name' => 'Mohiuddin Customer',
                ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'ORD-MATCH',

                'status' => OrderStatus::Processing,

                'payment_status' => PaymentStatus::Paid,

                'grand_total' => 10_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 10:00:00',
                    'Asia/Dhaka',
                ),
            ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'order_number' => 'ORD-NO-MATCH',

                'status' => OrderStatus::Cancelled,

                'payment_status' => PaymentStatus::Paid,

                'grand_total' => 99_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-11-10 11:00:00',
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
                    'admin.reports.sales.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'status' => [
                            'processing',
                        ],

                        'payment_status' => [
                            'paid',
                        ],

                        'search' => 'ORD-MATCH',

                        'per_page' => 50,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'filters.range.preset',
                        'custom',
                    )
                    ->where(
                        'filters.range.from',
                        '2026-11-01',
                    )
                    ->where(
                        'filters.range.to',
                        '2026-11-30',
                    )
                    ->where(
                        'filters.statuses',
                        [
                            'processing',
                        ],
                    )
                    ->where(
                        'filters.payment_statuses',
                        [
                            'paid',
                        ],
                    )
                    ->where(
                        'filters.search',
                        'ORD-MATCH',
                    )
                    ->where(
                        'filters.per_page',
                        50,
                    )
                    ->where(
                        'query.status',
                        [
                            'processing',
                        ],
                    )
                    ->where(
                        'query.payment_status',
                        [
                            'paid',
                        ],
                    )
                    ->where(
                        'query.search',
                        'ORD-MATCH',
                    )
                    ->where(
                        'query.per_page',
                        50,
                    )
                    ->where(
                        'report.meta.total',
                        1,
                    )
                    ->has(
                        'report.data',
                        1,
                    )
                    ->where(
                        'report.data.0.order_number',
                        'ORD-MATCH',
                    )
                    ->where(
                        'report.data.0.status',
                        'processing',
                    )
                    ->where(
                        'report.data.0.payment_status',
                        'paid',
                    ),
            );
    }

    public function test_sales_report_summary_represents_entire_filtered_result(): void
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
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'order_number' => sprintf(
                        'ORD-%02d',
                        $index,
                    ),

                    'subtotal' => 1_000,

                    'grand_total' => 1_000,

                    'placed_at' => CarbonImmutable::parse(
                        sprintf(
                            '2026-11-%02d 10:00:00',
                            $index,
                        ),
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'per_page' => 10,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.meta.total',
                        12,
                    )
                    ->where(
                        'report.meta.per_page',
                        10,
                    )
                    ->has(
                        'report.data',
                        10,
                    )
                    ->where(
                        'report.summary.subtotal',
                        12_000,
                    )
                    ->where(
                        'report.summary.grand_total',
                        12_000,
                    ),
            );
    }

    public function test_sales_report_pagination_page_is_forwarded_to_query(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        for (
            $index = 1;
            $index <= 15;
            $index++
        ) {
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'order_number' => sprintf(
                        'ORD-%02d',
                        $index,
                    ),

                    'placed_at' => CarbonImmutable::parse(
                        sprintf(
                            '2026-11-%02d 10:00:00',
                            $index,
                        ),
                        'Asia/Dhaka',
                    ),
                ]);
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'per_page' => 10,

                        'page' => 2,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'report.meta.current_page',
                        2,
                    )
                    ->where(
                        'report.meta.total',
                        15,
                    )
                    ->has(
                        'report.data',
                        5,
                    ),
            );
    }
}
