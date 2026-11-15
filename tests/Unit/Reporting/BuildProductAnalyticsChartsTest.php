<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use App\Domain\Reporting\Charts\BuildProductAnalyticsCharts;
use App\Domain\Reporting\Data\ProductAnalyticsProductRowData;
use App\Domain\Reporting\Data\ProductAnalyticsResultData;
use App\Domain\Reporting\Data\ProductAnalyticsSummaryData;
use App\Domain\Reporting\Data\ProductAnalyticsTrendRowData;
use PHPUnit\Framework\TestCase;

final class BuildProductAnalyticsChartsTest extends TestCase
{
    public function test_it_builds_all_product_analytics_chart_contracts(): void
    {
        $report =
            $this->report();

        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $report,
            );

        $this->assertSame(
            'sales_trend',
            $charts
                ->salesTrend
                ->key,
        );

        $this->assertSame(
            'top_products_by_revenue',
            $charts
                ->topProductsByRevenue
                ->key,
        );

        $this->assertSame(
            'top_products_by_quantity',
            $charts
                ->topProductsByQuantity
                ->key,
        );

        $this->assertSame(
            'current_vs_historical_revenue',
            $charts
                ->currentVsHistoricalRevenue
                ->key,
        );

        $this->assertSame(
            'sku_performance',
            $charts
                ->skuPerformance
                ->key,
        );

        $this->assertSame(
            'revenue_share_by_product',
            $charts
                ->revenueShareByProduct
                ->key,
        );
    }

    public function test_sales_trend_reconciles_with_report_summary(): void
    {
        $report =
            $this->report();

        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $report,
            );

        $grossSales =
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

        $quantity =
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

        $this->assertSame(
            $report
                ->summary
                ->grossSales,
            $grossSales,
        );

        $this->assertSame(
            $report
                ->summary
                ->quantitySold,
            $quantity,
        );
    }

    public function test_top_products_by_revenue_are_ranked_deterministically(): void
    {
        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $this->report(),
            );

        $this->assertSame(
            'Product B (SKU-B)',
            $charts
                ->topProductsByRevenue
                ->points[0]
                ->label,
        );

        $this->assertSame(
            15_000,
            $charts
                ->topProductsByRevenue
                ->points[0]
                ->values[
                'gross_sales'
            ],
        );

        $this->assertSame(
            'Product A (SKU-A)',
            $charts
                ->topProductsByRevenue
                ->points[1]
                ->label,
        );
    }

    public function test_top_products_by_quantity_are_ranked_by_quantity(): void
    {
        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $this->report(),
            );

        $this->assertSame(
            'Product A (SKU-A)',
            $charts
                ->topProductsByQuantity
                ->points[0]
                ->label,
        );

        $this->assertSame(
            3,
            $charts
                ->topProductsByQuantity
                ->points[0]
                ->values[
                'quantity_sold'
            ],
        );
    }

    public function test_current_and_historical_revenue_reconcile_with_summary(): void
    {
        $report =
            $this->report();

        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $report,
            );

        $points =
            $charts
                ->currentVsHistoricalRevenue
                ->points;

        $this->assertCount(
            2,
            $points,
        );

        $this->assertSame(
            $report
                ->summary
                ->currentProductRevenue,
            $points[0]
                ->values[
                'gross_sales'
            ],
        );

        $this->assertSame(
            $report
                ->summary
                ->historicalProductRevenue,
            $points[1]
                ->values[
                'gross_sales'
            ],
        );

        $this->assertSame(
            $report
                ->summary
                ->grossSales,
            (int)
            $points[0]
                ->values[
                'gross_sales'
            ] +
            (int)
            $points[1]
                ->values[
                'gross_sales'
            ],
        );
    }

    public function test_sku_performance_groups_duplicate_skus(): void
    {
        $report =
            new ProductAnalyticsResultData(
                summary: new ProductAnalyticsSummaryData(
                    productGroupsCount: 2,
                    quantitySold: 3,
                    ordersCount: 2,
                    grossSales: 15_000,
                    averageRevenuePerOrder: 7_500,
                    averageRevenuePerItem: 5_000,
                    currentProductRevenue: 15_000,
                    historicalProductRevenue: 0,
                ),

                trend: [],

                products: [
                    new ProductAnalyticsProductRowData(
                        productId: 1,
                        productName: 'Product One',
                        sku: 'SHARED-SKU',
                        historical: false,
                        quantitySold: 1,
                        ordersCount: 1,
                        grossSales: 5_000,
                        averageRevenuePerItem: 5_000,
                    ),

                    new ProductAnalyticsProductRowData(
                        productId: 2,
                        productName: 'Product Two',
                        sku: 'SHARED-SKU',
                        historical: false,
                        quantitySold: 2,
                        ordersCount: 1,
                        grossSales: 10_000,
                        averageRevenuePerItem: 5_000,
                    ),
                ],
            );

        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $report,
            );

        $this->assertCount(
            1,
            $charts
                ->skuPerformance
                ->points,
        );

        $point =
            $charts
                ->skuPerformance
                ->points[0];

        $this->assertSame(
            'SHARED-SKU',
            $point->label,
        );

        $this->assertSame(
            15_000,
            $point
                ->values[
                'gross_sales'
            ],
        );

        $this->assertSame(
            3,
            $point
                ->values[
                'quantity_sold'
            ],
        );
    }

    public function test_historical_products_get_stable_non_product_ids(): void
    {
        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $this->report(),
            );

        $historicalPoint =
            $charts
                ->topProductsByRevenue
                ->points[2];

        $this->assertStringStartsWith(
            'historical:',
            $historicalPoint->key,
        );

        $this->assertSame(
            1,
            $historicalPoint
                ->values[
                'historical'
            ],
        );
    }

    public function test_to_array_exposes_all_chart_keys(): void
    {
        $charts =
            (
                new BuildProductAnalyticsCharts
            )->build(
                $this->report(),
            );

        $array =
            $charts->toArray();

        $this->assertArrayHasKey(
            'sales_trend',
            $array,
        );

        $this->assertArrayHasKey(
            'top_products_by_revenue',
            $array,
        );

        $this->assertArrayHasKey(
            'top_products_by_quantity',
            $array,
        );

        $this->assertArrayHasKey(
            'current_vs_historical_revenue',
            $array,
        );

        $this->assertArrayHasKey(
            'sku_performance',
            $array,
        );

        $this->assertArrayHasKey(
            'revenue_share_by_product',
            $array,
        );
    }

    private function report(): ProductAnalyticsResultData
    {
        return new ProductAnalyticsResultData(
            summary: new ProductAnalyticsSummaryData(
                productGroupsCount: 3,

                quantitySold: 6,

                ordersCount: 3,

                grossSales: 30_000,

                averageRevenuePerOrder: 10_000,

                averageRevenuePerItem: 5_000,

                currentProductRevenue: 25_000,

                historicalProductRevenue: 5_000,
            ),

            trend: [
                new ProductAnalyticsTrendRowData(
                    date: '2026-11-10',

                    label: 'Nov 10, 2026',

                    quantitySold: 2,

                    ordersCount: 1,

                    grossSales: 10_000,

                    currentProductRevenue: 10_000,

                    historicalProductRevenue: 0,
                ),

                new ProductAnalyticsTrendRowData(
                    date: '2026-11-11',

                    label: 'Nov 11, 2026',

                    quantitySold: 4,

                    ordersCount: 2,

                    grossSales: 20_000,

                    currentProductRevenue: 15_000,

                    historicalProductRevenue: 5_000,
                ),
            ],

            products: [
                new ProductAnalyticsProductRowData(
                    productId: 1,

                    productName: 'Product A',

                    sku: 'SKU-A',

                    historical: false,

                    quantitySold: 3,

                    ordersCount: 2,

                    grossSales: 10_000,

                    averageRevenuePerItem: 3_333,
                ),

                new ProductAnalyticsProductRowData(
                    productId: 2,

                    productName: 'Product B',

                    sku: 'SKU-B',

                    historical: false,

                    quantitySold: 2,

                    ordersCount: 1,

                    grossSales: 15_000,

                    averageRevenuePerItem: 7_500,
                ),

                new ProductAnalyticsProductRowData(
                    productId: null,

                    productName: 'Historical Product',

                    sku: 'OLD-SKU',

                    historical: true,

                    quantitySold: 1,

                    ordersCount: 1,

                    grossSales: 5_000,

                    averageRevenuePerItem: 5_000,
                ),
            ],
        );
    }
}
