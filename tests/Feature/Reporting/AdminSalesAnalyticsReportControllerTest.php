<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminSalesAnalyticsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_sales_analytics(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.analytics',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_open_sales_analytics_dashboard(): void
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
                    'admin.reports.sales.analytics',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Reports/SalesAnalytics/Index',
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
                        'report.summary',
                    )
                    ->has(
                        'charts.revenue_trend',
                    )
                    ->has(
                        'charts.orders_trend',
                    )
                    ->has(
                        'charts.status_revenue',
                    )
                    ->has(
                        'charts.payment_status_revenue',
                    )
                    ->has(
                        'charts.payment_method_revenue',
                    ),
            );
    }

    public function test_sales_analytics_uses_authoritative_order_values(): void
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

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.analytics',
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
                        'report.summary.orders_count',
                        1,
                    )
                    ->where(
                        'report.summary.grand_total',
                        10_000,
                    )
                    ->where(
                        'charts.revenue_trend.points.0.values.grand_total',
                        10_000,
                    ),
            );
    }
}
