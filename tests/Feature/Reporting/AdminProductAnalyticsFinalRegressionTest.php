<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Charts\BuildProductAnalyticsCharts;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminProductAnalyticsReportQuery;
use App\Domain\Reporting\Queries\GetAdminProductPerformanceReportQuery;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

final class AdminProductAnalyticsFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_analytics_page_and_pdf_are_protected_from_guests(): void
    {
        $this
            ->get(
                route(
                    'admin.reports.products.analytics',
                ),
            )
            ->assertRedirect();

        $this
            ->get(
                route(
                    'admin.reports.products.analytics.pdf',
                ),
            )
            ->assertRedirect();
    }

    public function test_admin_can_access_product_analytics_and_pdf_export(): void
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
                    'admin.reports.products.analytics',
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
                    'admin.reports.products.analytics.pdf',
                ),
            )
            ->assertOk();

        Pdf::assertRespondedWithPdf(
            static function ($pdf): bool {
                return $pdf->contains(
                    'Product Analytics Report',
                );
            },
        );
    }

    public function test_product_analytics_summary_reconciles_with_existing_product_performance_report(): void
    {
        $customer =
            User::factory()
                ->create();

        $productA =
            Product::factory()
                ->create([
                    'name' => 'Product A',
                ]);

        $productB =
            Product::factory()
                ->create([
                    'name' => 'Product B',
                ]);

        $firstOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-FINAL-001',
                placedAt: '2026-11-05 10:00:00',
            );

        $this->createItem(
            order: $firstOrder,
            product: $productA,
            productName: 'Product A',
            sku: 'SKU-A',
            quantity: 2,
            lineTotal: 10_000,
        );

        $secondOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-FINAL-002',
                placedAt: '2026-11-06 10:00:00',
            );

        $this->createItem(
            order: $secondOrder,
            product: $productB,
            productName: 'Product B',
            sku: 'SKU-B',
            quantity: 3,
            lineTotal: 15_000,
        );

        $filters =
            $this->filters();

        $analytics =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $filters,
            );

        $performance =
            app(
                GetAdminProductPerformanceReportQuery::class,
            )->get(
                $filters,
                1,
            );

        $this->assertSame(
            $performance->productGroupsCount,
            $analytics
                ->summary
                ->productGroupsCount,
        );

        $this->assertSame(
            $performance->quantitySold,
            $analytics
                ->summary
                ->quantitySold,
        );

        $this->assertSame(
            $performance->ordersCount,
            $analytics
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            $performance->grossSales,
            $analytics
                ->summary
                ->grossSales,
        );

        $this->assertSame(
            12_500,
            $analytics
                ->summary
                ->averageRevenuePerOrder,
        );

        $this->assertSame(
            5_000,
            $analytics
                ->summary
                ->averageRevenuePerItem,
        );
    }

    public function test_chart_totals_reconcile_with_product_analytics_summary(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $first =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-CHART-001',
                placedAt: '2026-11-10 10:00:00',
            );

        $this->createItem(
            order: $first,
            product: $product,
            productName: 'Chart Product',
            sku: 'CHART-SKU',
            quantity: 2,
            lineTotal: 10_000,
        );

        $second =
            $this->createOrder(
                user: $customer,
                orderNumber: 'PRODUCT-CHART-002',
                placedAt: '2026-11-11 10:00:00',
            );

        $this->createItem(
            order: $second,
            product: $product,
            productName: 'Chart Product',
            sku: 'CHART-SKU',
            quantity: 3,
            lineTotal: 15_000,
        );

        $report =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $charts =
            app(
                BuildProductAnalyticsCharts::class,
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
                                'gross_sales'
                            ] ??
                            0
                        ),
                    $charts
                        ->salesTrend
                        ->points,
                ),
            );

        $trendQuantity =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'quantity_sold'
                            ] ??
                            0
                        ),
                    $charts
                        ->salesTrend
                        ->points,
                ),
            );

        $currentHistoricalTotal =
            array_sum(
                array_map(
                    static fn ($point): int => (int)
                        (
                            $point
                                ->values[
                                'gross_sales'
                            ] ??
                            0
                        ),
                    $charts
                        ->currentVsHistoricalRevenue
                        ->points,
                ),
            );

        $this->assertSame(
            $report
                ->summary
                ->grossSales,
            $trendRevenue,
        );

        $this->assertSame(
            $report
                ->summary
                ->quantitySold,
            $trendQuantity,
        );

        $this->assertSame(
            $report
                ->summary
                ->grossSales,
            $currentHistoricalTotal,
        );
    }

    public function test_historical_product_revenue_reconciles_with_deleted_product_snapshot(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Historical Final Product',
                ]);

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'HISTORICAL-FINAL-001',
                placedAt: '2026-11-12 10:00:00',
            );

        $item =
            $this->createItem(
                order: $order,
                product: $product,
                productName: 'Historical Final Product',
                sku: 'HIST-FINAL',
                quantity: 2,
                lineTotal: 9_000,
            );

        $item->update([
            'product_id' => null,
        ]);

        $product->delete();

        $report =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            0,
            $report
                ->summary
                ->currentProductRevenue,
        );

        $this->assertSame(
            9_000,
            $report
                ->summary
                ->historicalProductRevenue,
        );

        $this->assertCount(
            1,
            $report->products,
        );

        $this->assertTrue(
            $report
                ->products[0]
                ->historical,
        );

        $this->assertNull(
            $report
                ->products[0]
                ->productId,
        );

        $this->assertSame(
            'Historical Final Product',
            $report
                ->products[0]
                ->productName,
        );
    }

    public function test_cancelled_orders_remain_visible_without_explicit_filter_exclusion(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->createOrder(
                user: $customer,
                orderNumber: 'CANCELLED-PRODUCT-FINAL',
                placedAt: '2026-11-13 10:00:00',
                status: OrderStatus::Cancelled,
                paymentStatus: PaymentStatus::Unpaid,
            );

        $this->createItem(
            order: $order,
            product: $product,
            productName: 'Cancelled Product',
            sku: 'CANCELLED-FINAL',
            quantity: 1,
            lineTotal: 7_000,
        );

        $report =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $this->filters(),
            );

        $this->assertSame(
            1,
            $report
                ->summary
                ->ordersCount,
        );

        $this->assertSame(
            7_000,
            $report
                ->summary
                ->grossSales,
        );
    }

    public function test_filters_apply_consistently_to_product_analytics_and_charts(): void
    {
        $customer =
            User::factory()
                ->create();

        $matchingProduct =
            Product::factory()
                ->create([
                    'name' => 'Special Final Product',
                ]);

        $otherProduct =
            Product::factory()
                ->create([
                    'name' => 'Other Final Product',
                ]);

        $matchingOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'FILTER-FINAL-001',
                placedAt: '2026-11-14 10:00:00',
                status: OrderStatus::Completed,
                paymentStatus: PaymentStatus::Paid,
            );

        $this->createItem(
            order: $matchingOrder,
            product: $matchingProduct,
            productName: 'Special Final Product',
            sku: 'SPECIAL-FINAL',
            quantity: 2,
            lineTotal: 11_000,
        );

        $otherOrder =
            $this->createOrder(
                user: $customer,
                orderNumber: 'FILTER-FINAL-002',
                placedAt: '2026-11-14 11:00:00',
                status: OrderStatus::Processing,
                paymentStatus: PaymentStatus::Due,
            );

        $this->createItem(
            order: $otherOrder,
            product: $otherProduct,
            productName: 'Other Final Product',
            sku: 'OTHER-FINAL',
            quantity: 3,
            lineTotal: 22_000,
        );

        $filters =
            $this->filters(
                search: 'SPECIAL-FINAL',
                statuses: [
                    OrderStatus::Completed,
                ],
                paymentStatuses: [
                    PaymentStatus::Paid,
                ],
            );

        $report =
            app(
                GetAdminProductAnalyticsReportQuery::class,
            )->get(
                $filters,
            );

        $charts =
            app(
                BuildProductAnalyticsCharts::class,
            )->build(
                $report,
            );

        $this->assertSame(
            1,
            $report
                ->summary
                ->productGroupsCount,
        );

        $this->assertSame(
            2,
            $report
                ->summary
                ->quantitySold,
        );

        $this->assertSame(
            11_000,
            $report
                ->summary
                ->grossSales,
        );

        $this->assertSame(
            11_000,
            $charts
                ->salesTrend
                ->points[0]
                ->values[
                'gross_sales'
            ],
        );

        $this->assertSame(
            'Special Final Product (SPECIAL-FINAL)',
            $charts
                ->topProductsByRevenue
                ->points[0]
                ->label,
        );
    }

    public function test_product_analytics_pdf_view_contains_all_kpis_charts_and_summary_contracts(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'views/admin/reports/product-analytics-pdf.blade.php',
                ),
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'Product Analytics Report',
            $source,
        );

        $this->assertStringContainsString(
            'Product Groups',
            $source,
        );

        $this->assertStringContainsString(
            'Gross Sales',
            $source,
        );

        $this->assertStringContainsString(
            'Current Product Revenue',
            $source,
        );

        $this->assertStringContainsString(
            'Historical Product Revenue',
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['sales_trend']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['top_products_by_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['top_products_by_quantity']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['current_vs_historical_revenue']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['sku_performance']",
            $source,
        );

        $this->assertStringContainsString(
            "chartSvgs['revenue_share_by_product']",
            $source,
        );

        $this->assertStringContainsString(
            'Product Performance Summary',
            $source,
        );

        $this->assertStringContainsString(
            '@forelse ($report->products as $row)',
            $source,
        );
    }

    public function test_product_analytics_frontend_keeps_pdf_chart_and_navigation_contracts(): void
    {
        $page =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Reports/ProductAnalytics/Index.tsx',
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
            'activeReport="product-analytics"',
            $page,
        );

        $this->assertStringContainsString(
            "'admin.reports.products.analytics.pdf'",
            $page,
        );

        $this->assertStringContainsString(
            'Download PDF',
            $page,
        );

        $this->assertStringContainsString(
            'charts.sales_trend',
            $page,
        );

        $this->assertStringContainsString(
            'charts.top_products_by_revenue',
            $page,
        );

        $this->assertStringContainsString(
            'charts.top_products_by_quantity',
            $page,
        );

        $this->assertStringContainsString(
            'charts.current_vs_historical_revenue',
            $page,
        );

        $this->assertStringContainsString(
            'charts.sku_performance',
            $page,
        );

        $this->assertStringContainsString(
            'charts.revenue_share_by_product',
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
        array $statuses = [],
        array $paymentStatuses = [],
    ): ProductPerformanceReportFiltersData {
        return new ProductPerformanceReportFiltersData(
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

            statuses: $statuses,

            paymentStatuses: $paymentStatuses,

            search: $search,

            perPage: 25,
        );
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $placedAt,
        OrderStatus $status =
        OrderStatus::Completed,
        PaymentStatus $paymentStatus =
        PaymentStatus::Paid,
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

                'subtotal' => 0,

                'discount_total' => 0,

                'delivery_total' => 0,

                'tax_total' => 0,

                'grand_total' => 0,

                'placed_at' => CarbonImmutable::parse(
                    $placedAt,
                    'Asia/Dhaka',
                ),
            ]);
    }

    private function createItem(
        Order $order,
        ?Product $product,
        string $productName,
        ?string $sku,
        int $quantity,
        int $lineTotal,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product?->id,

                'product_name' => $productName,

                'sku' => $sku,

                'unit_price' => intdiv(
                    $lineTotal,
                    $quantity,
                ),

                'quantity' => $quantity,

                'line_total' => $lineTotal,
            ]);
    }
}
