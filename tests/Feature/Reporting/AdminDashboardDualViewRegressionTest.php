<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminDashboardDualViewRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this
            ->get(
                route(
                    'admin.dashboard',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_dashboard_exposes_single_authoritative_dataset_for_both_views(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.dashboard',
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Dashboard',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'metrics',
                    )
                    ->has(
                        'trend',
                    )
                    ->has(
                        'breakdowns.payment',
                    )
                    ->has(
                        'breakdowns.fulfilment',
                    )
                    ->has(
                        'recentOrders',
                    )
                    ->has(
                        'operationalSummary',
                    )
                    ->has(
                        'topProducts',
                    )
                    ->has(
                        'inventorySummary',
                    )
                    ->has(
                        'inventoryAlerts',
                    ),
            );
    }

    public function test_dashboard_source_contains_classic_and_graphical_switch_contract(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            "'classic'",
            $source,
        );

        $this->assertStringContainsString(
            "'graphical'",
            $source,
        );

        $this->assertStringContainsString(
            'DashboardViewSwitch',
            $source,
        );

        $this->assertStringContainsString(
            'aria-pressed',
            $source,
        );

        $this->assertStringContainsString(
            'aria-controls="dashboard-view-panel"',
            $source,
        );

        $this->assertStringContainsString(
            'id="dashboard-view-panel"',
            $source,
        );
    }

    public function test_dashboard_view_preference_is_persisted_locally_only(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            'DASHBOARD_VIEW_STORAGE_KEY',
            $source,
        );

        $this->assertStringContainsString(
            'window.localStorage.getItem',
            $source,
        );

        $this->assertStringContainsString(
            'window.localStorage.setItem',
            $source,
        );

        $this->assertStringContainsString(
            "'mwt-admin-dashboard-view'",
            $source,
        );

        $this->assertStringNotContainsString(
            'dashboard_view:',
            $source,
        );

        $this->assertStringNotContainsString(
            'dashboardView:',
            $source,
        );
    }

    public function test_graphical_dashboard_uses_same_metrics_as_classic_dashboard(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            'metrics.net_sales',
            $source,
        );

        $this->assertStringContainsString(
            'metrics.orders_count',
            $source,
        );

        $this->assertStringContainsString(
            'metrics.customers_count',
            $source,
        );

        $this->assertStringContainsString(
            'metrics.products_sold',
            $source,
        );

        $this->assertStringContainsString(
            'metrics.average_order_value',
            $source,
        );
    }

    public function test_graphical_revenue_and_orders_charts_use_authoritative_trend_data(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            '<RevenueTrendChart',
            $source,
        );

        $this->assertStringContainsString(
            '<OrdersTrendChart',
            $source,
        );

        $this->assertStringContainsString(
            'point.net_sales',
            $source,
        );

        $this->assertStringContainsString(
            'orders_count',
            $source,
        );

        $this->assertStringContainsString(
            'dataKey="net_sales"',
            $source,
        );

        $this->assertStringContainsString(
            'dataKey="orders_count"',
            $source,
        );
    }

    public function test_graphical_payment_and_fulfilment_charts_use_existing_breakdowns(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            '<PaymentBreakdownChart',
            $source,
        );

        $this->assertStringContainsString(
            '<FulfilmentBreakdownChart',
            $source,
        );

        $this->assertStringContainsString(
            'breakdowns.payment',
            $source,
        );

        $this->assertStringContainsString(
            'breakdowns.fulfilment',
            $source,
        );

        $this->assertStringContainsString(
            'item.orders_count',
            $source,
        );

        $this->assertStringContainsString(
            'item.total',
            $source,
        );
    }

    public function test_graphical_inventory_chart_uses_existing_inventory_summary(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            '<InventoryHealthChart',
            $source,
        );

        $this->assertStringContainsString(
            'inventorySummary',
            $source,
        );

        $this->assertStringContainsString(
            '.in_stock_products_count',
            $source,
        );

        $this->assertStringContainsString(
            '.low_stock_products_count',
            $source,
        );

        $this->assertStringContainsString(
            '.out_of_stock_products_count',
            $source,
        );

        $this->assertStringContainsString(
            '.untracked_products_count',
            $source,
        );
    }

    public function test_graphical_top_products_chart_uses_existing_top_products_data(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            '<TopProductsRevenueChart',
            $source,
        );

        $this->assertStringContainsString(
            'topProducts',
            $source,
        );

        $this->assertStringContainsString(
            'product.product_name',
            $source,
        );

        $this->assertStringContainsString(
            'product.sku',
            $source,
        );

        $this->assertStringContainsString(
            'product.gross_sales',
            $source,
        );
    }

    public function test_graphical_dashboard_has_accessible_chart_contracts(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            'aria-label="Net revenue trend chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Orders trend chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Payment breakdown chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Fulfilment breakdown chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Inventory health chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-label="Top products by revenue chart"',
            $source,
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $source,
        );

        $this->assertStringContainsString(
            'No trend data for the',
            $source,
        );

        $this->assertStringContainsString(
            'selected range.',
            $source,
        );
    }

    public function test_graphical_dashboard_remains_responsive(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            'sm:grid-cols-2',
            $source,
        );

        $this->assertStringContainsString(
            'xl:grid-cols-2',
            $source,
        );

        $this->assertStringContainsString(
            'xl:grid-cols-4',
            $source,
        );

        $this->assertStringContainsString(
            'h-64',
            $source,
        );

        $this->assertStringContainsString(
            'sm:h-72',
            $source,
        );

        $this->assertStringContainsString(
            'lg:h-80',
            $source,
        );

        $this->assertStringContainsString(
            'min-w-0',
            $source,
        );
    }

    public function test_dashboard_date_filter_still_targets_same_admin_dashboard_route(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            "'admin.dashboard'",
            $source,
        );

        $this->assertStringContainsString(
            'router.get(',
            $source,
        );

        $this->assertStringContainsString(
            "preset === 'custom'",
            $source,
        );

        $this->assertStringContainsString(
            'query.from',
            $source,
        );

        $this->assertStringContainsString(
            'query.to',
            $source,
        );
    }

    public function test_graphical_dashboard_does_not_call_a_separate_reporting_endpoint(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringNotContainsString(
            'admin.dashboard.graphical',
            $source,
        );

        $this->assertStringNotContainsString(
            '/admin/dashboard/graphical',
            $source,
        );

        $this->assertStringNotContainsString(
            'fetch(',
            $source,
        );

        $this->assertStringNotContainsString(
            'axios.',
            $source,
        );
    }

    public function test_recharts_components_required_by_graphical_dashboard_are_present(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringContainsString(
            'ResponsiveContainer',
            $source,
        );

        $this->assertStringContainsString(
            'LineChart',
            $source,
        );

        $this->assertStringContainsString(
            'BarChart',
            $source,
        );

        $this->assertStringContainsString(
            'PieChart',
            $source,
        );

        $this->assertStringContainsString(
            '<Tooltip',
            $source,
        );
    }

    public function test_tooltip_formatters_do_not_use_overly_narrow_manual_value_types(): void
    {
        $source =
            $this->dashboardSource();

        $this->assertStringNotContainsString(
            'value:\n                            number |\n                            string',
            $source,
        );

        $this->assertStringContainsString(
            'value ?? 0',
            $source,
        );
    }

    private function dashboardSource(): string
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Dashboard.tsx',
                ),
            );

        $this->assertIsString(
            $source,
        );

        return $source;
    }
}
