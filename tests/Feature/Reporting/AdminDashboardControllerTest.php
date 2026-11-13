<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
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

final class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_dashboard_requires_admin_authentication(): void
    {
        $this
            ->get(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_dashboard_uses_this_month_as_default_reporting_range(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-11-13 12:00:00',
                config(
                    'app.timezone',
                ),
            ),
        );

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
                    'admin.dashboard',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Dashboard',
                    )
                    ->where(
                        'filters.range.preset',
                        'this_month',
                    )
                    ->where(
                        'filters.range.from',
                        '2026-11-01',
                    )
                    ->where(
                        'filters.range.to',
                        '2026-11-30',
                    )
                    ->has(
                        'filters.presets',
                        6,
                    )
                    ->has(
                        'metrics',
                    ),
            );
    }

    public function test_dashboard_exposes_authoritative_metrics_for_custom_range(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $included =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'payment_status' => PaymentStatus::Paid,

                    'subtotal' => 10_000,

                    'discount_total' => 1_000,

                    'delivery_total' => 800,

                    'tax_total' => 200,

                    'grand_total' => 10_000,

                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);

        OrderItem::factory()
            ->for(
                $included,
            )
            ->create([
                'unit_price' => 5_000,

                'quantity' => 2,

                'line_total' => 10_000,
            ]);

        Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'payment_status' => PaymentStatus::Paid,

                'subtotal' => 99_000,

                'grand_total' => 99_000,

                'placed_at' => CarbonImmutable::parse(
                    '2026-10-15 10:00:00',
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
                    'admin.dashboard',
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
                fn (Assert $page) => $page
                    ->where(
                        'filters.range',
                        [
                            'preset' => 'custom',

                            'preset_label' => 'Custom range',

                            'from' => '2026-11-01',

                            'to' => '2026-11-30',

                            'timezone' => 'Asia/Dhaka',
                        ],
                    )
                    ->where(
                        'metrics.orders_count',
                        1,
                    )
                    ->where(
                        'metrics.gross_sales',
                        10_000,
                    )
                    ->where(
                        'metrics.discount_total',
                        1_000,
                    )
                    ->where(
                        'metrics.shipping_total',
                        800,
                    )
                    ->where(
                        'metrics.tax_total',
                        200,
                    )
                    ->where(
                        'metrics.net_sales',
                        10_000,
                    )
                    ->where(
                        'metrics.paid_orders_count',
                        1,
                    )
                    ->where(
                        'metrics.paid_total',
                        10_000,
                    )
                    ->where(
                        'metrics.products_sold',
                        2,
                    )
                    ->where(
                        'metrics.customers_count',
                        1,
                    )
                    ->where(
                        'metrics.average_order_value',
                        10_000,
                    ),
            );
    }

    public function test_dashboard_rejects_invalid_preset(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.dashboard',
                ),
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'not-a-real-range',
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertSessionHasErrors([
                'preset',
            ]);
    }

    public function test_custom_dashboard_range_requires_from_and_to_dates(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.dashboard',
                ),
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'custom',
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertSessionHasErrors([
                'from',
                'to',
            ]);
    }

    public function test_custom_dashboard_rejects_reverse_range(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.dashboard',
                ),
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-20',

                        'to' => '2026-11-10',
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertSessionHasErrors([
                'to',
            ]);
    }

    public function test_dashboard_rejects_invalid_timezone(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.dashboard',
                ),
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'today',

                        'timezone' => 'Invalid/Timezone',
                    ],
                ),
            )
            ->assertRedirect(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertSessionHasErrors([
                'timezone',
            ]);
    }

    public function test_dashboard_exposes_complete_operational_reporting_payload(): void
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
                    'status' => OrderStatus::Processing,

                    'payment_status' => PaymentStatus::Due,

                    'fulfilment_status' => FulfilmentStatus::Shipped,

                    'subtotal' => 10_000,

                    'discount_total' => 0,

                    'delivery_total' => 500,

                    'tax_total' => 0,

                    'grand_total' => 10_500,

                    'placed_at' => CarbonImmutable::parse(
                        '2026-11-10 10:00:00',
                        'Asia/Dhaka',
                    ),
                ]);

        OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'unit_price' => 5_000,

                'quantity' => 2,

                'line_total' => 10_000,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.dashboard',
                    [
                        'preset' => 'custom',

                        'from' => '2026-11-10',

                        'to' => '2026-11-10',

                        'timezone' => 'Asia/Dhaka',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Dashboard',
                    )
                    ->where(
                        'metrics.orders_count',
                        1,
                    )
                    ->has(
                        'trend',
                        1,
                    )
                    ->where(
                        'trend.0.orders_count',
                        1,
                    )
                    ->where(
                        'trend.0.net_sales',
                        10_500,
                    )
                    ->where(
                        'trend.0.products_sold',
                        2,
                    )
                    ->has(
                        'breakdowns.payment',
                        count(
                            PaymentStatus::cases(),
                        ),
                    )
                    ->has(
                        'breakdowns.fulfilment',
                        count(
                            FulfilmentStatus::cases(),
                        ),
                    )
                    ->has(
                        'recentOrders',
                        1,
                    )
                    ->where(
                        'recentOrders.0.id',
                        $order->id,
                    )
                    ->where(
                        'recentOrders.0.payment_status',
                        'due',
                    )
                    ->where(
                        'recentOrders.0.fulfilment_status',
                        'shipped',
                    )
                    ->where(
                        'operationalSummary.processing_orders_count',
                        1,
                    )
                    ->where(
                        'operationalSummary.shipped_orders_count',
                        1,
                    )
                    ->where(
                        'operationalSummary.due_payment_orders_count',
                        1,
                    )
                    ->where(
                        'operationalSummary.due_payment_total',
                        10_500,
                    ),
            );
    }

    public function test_dashboard_exposes_product_and_inventory_reporting_payload(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Dashboard Product',

                    'sku' => 'DASH-001',

                    'track_inventory' => true,

                    'stock_quantity' => 3,
                ]);

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'grand_total' => 10_000,

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
                'product_name' => 'Dashboard Product',

                'sku' => 'DASH-001',

                'unit_price' => 5_000,

                'quantity' => 2,

                'line_total' => 10_000,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.dashboard',
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
                fn (Assert $page) => $page
                    ->has(
                        'topProducts',
                        1,
                    )
                    ->where(
                        'topProducts.0.product_id',
                        $product->id,
                    )
                    ->where(
                        'topProducts.0.product_name',
                        'Dashboard Product',
                    )
                    ->where(
                        'topProducts.0.quantity_sold',
                        2,
                    )
                    ->where(
                        'topProducts.0.gross_sales',
                        10_000,
                    )
                    ->where(
                        'inventorySummary.tracked_products_count',
                        1,
                    )
                    ->where(
                        'inventorySummary.low_stock_products_count',
                        1,
                    )
                    ->where(
                        'inventorySummary.out_of_stock_products_count',
                        0,
                    )
                    ->where(
                        'inventorySummary.total_units_in_stock',
                        3,
                    )
                    ->where(
                        'inventorySummary.low_stock_threshold',
                        5,
                    )
                    ->has(
                        'inventoryAlerts',
                        1,
                    )
                    ->where(
                        'inventoryAlerts.0.product_id',
                        $product->id,
                    )
                    ->where(
                        'inventoryAlerts.0.stock_quantity',
                        3,
                    )
                    ->where(
                        'inventoryAlerts.0.out_of_stock',
                        false,
                    ),
            );
    }
}
