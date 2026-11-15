<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Charts\BuildSalesAnalyticsCharts;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminSalesAnalyticsReportQuery;
use App\Domain\Reporting\Queries\GetAdminSalesReportQuery;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

final class AdminSalesAnalyticsFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_analytics_page_and_pdf_are_protected_from_guests(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.sales.analytics',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.sales.analytics.pdf',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_sales_analytics_and_pdf_export(): void
    {
        Pdf::fake();

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
            ->assertOk();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.reports.sales.analytics.pdf',
                ),
            )
            ->assertOk();

        Pdf::assertRespondedWithPdf(
            static function ($pdf): bool {
                return $pdf->contains(
                    'Sales Analytics Report',
                );
            },
        );
    }

    public function test_sales_analytics_summary_reconciles_with_existing_sales_report(): void
    {
        $customer =
            User::factory()
                ->create([
                    'name' => 'Analytics Reconciliation Customer',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'ANALYTICS-FINAL-001',
                placedAt: '2026-11-05 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 10_000,
                discountTotal: 1_000,
                deliveryTotal: 800,
                taxTotal: 200,
                grandTotal: 10_000,
            );

        $this->createItem(
            order: $firstOrder,
            quantity: 2,
            lineTotal: 10_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'ANALYTICS-FINAL-002',
                placedAt: '2026-11-06 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Due,
                subtotal: 20_000,
                discountTotal: 2_000,
                deliveryTotal: 1_200,
                taxTotal: 300,
                grandTotal: 19_500,
            );

        $this->createItem(
            order: $secondOrder,
            quantity: 3,
            lineTotal: 20_000,
        );

        $filters =
            $this->filters();

        $analytics =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $filters,
            );

        $salesReport =
            app(
                GetAdminSalesReportQuery::class,
            )->get(
                $filters,
                1,
            );

        $this->assertSame(
            $salesReport->total,
            $analytics
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            $salesReport->subtotal,
            $analytics
                ->summary
                ->subtotal,
        );

        $this->assertSame(
            $salesReport->discountTotal,
            $analytics
                ->summary
                ->discountTotal,
        );

        $this->assertSame(
            $salesReport->deliveryTotal,
            $analytics
                ->summary
                ->deliveryTotal,
        );

        $this->assertSame(
            $salesReport->taxTotal,
            $analytics
                ->summary
                ->taxTotal,
        );

        $this->assertSame(
            $salesReport->grandTotal,
            $analytics
                ->summary
                ->grandTotal,
        );

        $this->assertSame(
            $salesReport->itemsQuantity,
            $analytics
                ->summary
                ->itemsQuantity,
        );

        $this->assertSame(
            10_000,
            $analytics
                ->summary
                ->paidRevenue,
        );

        $this->assertSame(
            14_750,
            $analytics
                ->summary
                ->averageOrderValue,
        );
    }

    public function test_chart_totals_reconcile_with_sales_analytics_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $first =
            $this->createOrder(
                user: $customer,
                orderNumber: 'CHART-FINAL-001',
                placedAt: '2026-11-10 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
                subtotal: 10_000,
                grandTotal: 10_000,
            );

        $this->createItem(
            order: $first,
            quantity: 2,
            lineTotal: 10_000,
        );

        $second =
            $this->createOrder(
                user: $customer,
                orderNumber: 'CHART-FINAL-002',
                placedAt: '2026-11-11 10:00:00',
                status: OrderStatus::Processing,
                paymentStatus: PaymentStatus::Due,
                subtotal: 15_000,
                grandTotal: 15_000,
            );

        $this->createItem(
            order: $second,
            quantity: 1,
            lineTotal: 15_000,
        );

        $report =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $charts =
            app(
                BuildSalesAnalyticsCharts::class,
            )->build(
                $report,
            );

        $trendRevenue =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'grand_total'
                            ] ??
                            0
                        ),
                    $charts
                        ->revenueTrend
                        ->points,
                ),
            );

        $trendPaidRevenue =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'paid_revenue'
                            ] ??
                            0
                        ),
                    $charts
                        ->revenueTrend
                        ->points,
                ),
            );

        $trendOrders =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'orders_count'
                            ] ??
                            0
                        ),
                    $charts
                        ->ordersTrend
                        ->points,
                ),
            );

        $statusRevenue =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'grand_total'
                            ] ??
                            0
                        ),
                    $charts
                        ->statusRevenue
                        ->points,
                ),
            );

        $this->assertSame(
            $report
                ->summary
                ->grandTotal,
            $trendRevenue,
        );

        $this->assertSame(
            $report
                ->summary
                ->paidRevenue,
            $trendPaidRevenue,
        );

        $this->assertSame(
            $report
                ->summary
                ->ordersCount,
            $trendOrders,
        );

        $this->assertSame(
            $report
                ->summary
                ->grandTotal,
            $statusRevenue,
        );
    }

    public function test_cancelled_orders_remain_visible_without_explicit_exclusion(): void
    {
        $customer =
            User::factory()
                ->create();

        $this->createOrder(
            user: $customer,
            orderNumber: 'ANALYTICS-CANCELLED',
            placedAt: '2026-11-12 10:00:00',
            status: OrderStatus::Cancelled,
            paymentStatus: PaymentStatus::Unpaid,
            subtotal: 9_000,
            grandTotal: 9_000,
        );

        $result =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $result
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            OrderStatus::Cancelled->value,
            $result
                ->statusBreakdown[0]
                ->key,
        );
    }

    public function test_filters_apply_consistently_to_analytics_and_charts(): void
    {
        $matchingCustomer =
            User::factory()
                ->create([
                    'name' => 'Special Analytics Customer',
                ]);

        $otherCustomer =
            User::factory()
                ->create([
                    'name' => 'Other Customer',
                ]);

        $this->createOrder(
            user: $matchingCustomer,
            orderNumber: 'SPECIAL-001',
            placedAt: '2026-11-15 10:00:00',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            subtotal: 11_000,
            grandTotal: 11_000,
        );

        $this->createOrder(
            user: $otherCustomer,
            orderNumber: 'OTHER-001',
            placedAt: '2026-11-15 11:00:00',
            status: OrderStatus::Completed,
            paymentStatus: PaymentStatus::Paid,
            subtotal: 22_000,
            grandTotal: 22_000,
        );

        $filters =
            $this->filters(
                search: 'Special',
            );

        $report =
            app(
                GetAdminSalesAnalyticsReportQuery::class,
            )->get(
                $filters,
            );

        $charts =
            app(
                BuildSalesAnalyticsCharts::class,
            )->build(
                $report,
            );

        $this->assertSame(
            1,
            $report
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            11_000,
            $report
                ->summary
                ->grandTotal,
        );

        $this->assertSame(
            11_000,
            $charts
                ->revenueTrend
                ->points[0]
                ->values[
                'grand_total'
            ],
        );
    }

    public function test_pdf_view_contains_kpis_charts_and_daily_summary_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/admin/reports/sales-analytics-pdf.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Sales Analytics Report',
            $source,
        );

        $this->assertStringContainsString(
            'Total Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'Paid Revenue',
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['revenue_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['orders_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['status_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['payment_status_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['payment_method_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            'Daily Sales Summary',
            $source,
        );

        $this->assertStringContainsString(
            '@forelse ($report->trend as $row)',
            $source,
        );
    }

    public function test_sales_analytics_frontend_keeps_pdf_and_chart_contracts(): void
    {
        $page =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/SalesAnalytics/Index.tsx',
                ),
            );

        $chart =
            file_get_contents(
                resource_path(
                    'js/Components/Admin/Reports/AnalyticsChart.tsx',
                ),
            );

        $this->assertIsString(
            $page,
        );

        $this->assertIsString(
            $chart,
        );

        $this->assertStringContainsString(
            "'admin.reports.sales.analytics.pdf'",
            $page,
        );

        $this->assertStringContainsString(
            'Download PDF',
            $page,
        );

        $this->assertStringContainsString(
            'charts.revenue_trend',
            $page,
        );

        $this->assertStringContainsString(
            'charts.orders_trend',
            $page,
        );

        $this->assertStringContainsString(
            'ResponsiveContainer',
            $chart,
        );

        $this->assertStringContainsString(
            'useHorizontalBars',
            $chart,
        );

        $this->assertStringContainsString(
            'formatAxisValue',
            $chart,
        );
    }

    private function filters(
        ?string $search = null,
    ): SalesReportFiltersData {
        return new SalesReportFiltersData(
            range: new ReportDateRangeData(
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
            ),

            statuses: [],

            paymentStatuses: [],

            search: $search,

            perPage: 25,
        );
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        int $subtotal,
        int $grandTotal,
        int $discountTotal = 0,
        int $deliveryTotal = 0,
        int $taxTotal = 0,
    ): Order {
        return Order::factory()
            ->for(
                $user,
            )
            ->create([
                'order_number' => $orderNumber,

                'status' => $status,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => $paymentStatus,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'currency_code' => 'BDT',

                'subtotal' => $subtotal,

                'discount_total' => $discountTotal,

                'delivery_total' => $deliveryTotal,

                'tax_total' => $taxTotal,

                'grand_total' => $grandTotal,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_name' => 'Sales Analytics Product',

                'sku' => 'SALES-ANALYTICS-SKU',

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
