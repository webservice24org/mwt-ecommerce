<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminProductPerformanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_performance_report_requires_admin_authentication(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.products.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_product_performance_report_renders_expected_component(): void
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
                    'admin.reports.products.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Reports/Products/Index',
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

    public function test_product_performance_report_exposes_default_contract(): void
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
                    'admin.reports.products.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
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

    public function test_product_performance_report_exposes_filter_options(): void
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
                    'admin.reports.products.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
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

    public function test_controller_applies_filters_and_returns_product_rows(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Completed,

                    'payment_status' => PaymentStatus::Paid,

                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);

        OrderItem::factory()
            ->for(
                $order,
            )
            ->for(
                $product,
            )
            ->create([
                'product_name' => 'Performance Product',

                'sku' => 'PERF-001',

                'quantity' => 3,

                'unit_price' => 10_000,

                'line_total' => 30_000,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',

                        'status' => [
                            'completed',
                        ],

                        'payment_status' => [
                            'paid',
                        ],

                        'search' => 'PERF',

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
                        'filters.range.preset',
                        'custom',
                    )
                    ->where(
                        'filters.statuses',
                        [
                            'completed',
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
                        'PERF',
                    )
                    ->where(
                        'filters.per_page',
                        50,
                    )
                    ->where(
                        'query.search',
                        'PERF',
                    )
                    ->where(
                        'report.meta.total',
                        1,
                    )
                    ->where(
                        'report.data.0.product_name',
                        'Performance Product',
                    )
                    ->where(
                        'report.data.0.sku',
                        'PERF-001',
                    )
                    ->where(
                        'report.data.0.quantity_sold',
                        3,
                    )
                    ->where(
                        'report.data.0.gross_sales',
                        30_000,
                    ),
            );
    }

    public function test_deleted_product_history_remains_visible_through_controller(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);

        $item =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->for(
                    $product,
                )
                ->create([
                    'product_name' => 'Deleted Product',

                    'sku' => 'OLD-SKU',

                    'quantity' => 2,

                    'unit_price' => 5_000,

                    'line_total' => 10_000,
                ]);

        $product->delete();

        $item->refresh();

        $this->assertNull(
            $item->product_id,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-01',

                        'to' => '2026-11-30',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->where(
                        'report.meta.total',
                        1,
                    )
                    ->where(
                        'report.data.0.product_id',
                        null,
                    )
                    ->where(
                        'report.data.0.product_name',
                        'Deleted Product',
                    )
                    ->where(
                        'report.data.0.sku',
                        'OLD-SKU',
                    ),
            );
    }

    public function test_product_performance_summary_covers_full_filtered_result(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);

        for (
            $index = 1;
            $index <= 12;
            $index++
        ) {
            $product =
                Product::factory()
                    ->create();

            OrderItem::factory()
                ->for(
                    $order,
                )
                ->for(
                    $product,
                )
                ->create([
                    'product_name' => sprintf(
                        'Product %02d',
                        $index,
                    ),

                    'sku' => sprintf(
                        'SKU-%02d',
                        $index,
                    ),

                    'quantity' => 1,

                    'unit_price' => 1_000,

                    'line_total' => 1_000,
                ]);
        }

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.products.index',
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
                fn (
                    Assert $page,
                ) => $page
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
                        'report.summary.product_groups_count',
                        12,
                    )
                    ->where(
                        'report.summary.quantity_sold',
                        12,
                    )
                    ->where(
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.gross_sales',
                        12_000,
                    ),
            );
    }
}
