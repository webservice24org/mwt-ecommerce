<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Charts;

use App\Domain\Reporting\Data\Charts\ProductAnalyticsChartsData;
use App\Domain\Reporting\Data\Charts\ReportChartData;
use App\Domain\Reporting\Data\Charts\ReportChartPointData;
use App\Domain\Reporting\Data\Charts\ReportChartSeriesData;
use App\Domain\Reporting\Data\ProductAnalyticsProductRowData;
use App\Domain\Reporting\Data\ProductAnalyticsResultData;

final class BuildProductAnalyticsCharts
{
    private const TOP_PRODUCTS_LIMIT = 10;

    public function build(
        ProductAnalyticsResultData $report,
    ): ProductAnalyticsChartsData {
        return new ProductAnalyticsChartsData(
            salesTrend: $this->salesTrend(
                $report,
            ),

            topProductsByRevenue: $this->topProductsByRevenue(
                $report,
            ),

            topProductsByQuantity: $this->topProductsByQuantity(
                $report,
            ),

            currentVsHistoricalRevenue: $this->currentVsHistoricalRevenue(
                $report,
            ),

            skuPerformance: $this->skuPerformance(
                $report,
            ),

            revenueShareByProduct: $this->revenueShareByProduct(
                $report,
            ),
        );
    }

    private function salesTrend(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        return new ReportChartData(
            key: 'sales_trend',

            title: 'Product Sales Trend',

            description: 'Daily product revenue and quantity sold for the selected filters.',

            type: 'line',

            series: [
                new ReportChartSeriesData(
                    key: 'gross_sales',

                    label: 'Gross Sales',

                    valueType: 'money',
                ),

                new ReportChartSeriesData(
                    key: 'quantity_sold',

                    label: 'Quantity Sold',

                    valueType: 'integer',
                ),
            ],

            points: array_map(
                static fn (
                    $row,
                ): ReportChartPointData => new ReportChartPointData(
                    key: $row->date,

                    label: $row->label,

                    values: [
                        'gross_sales' => $row->grossSales,

                        'quantity_sold' => $row->quantitySold,

                        'orders_count' => $row->ordersCount,

                        'current_product_revenue' => $row->currentProductRevenue,

                        'historical_product_revenue' => $row->historicalProductRevenue,
                    ],
                ),
                $report->trend,
            ),
        );
    }

    private function topProductsByRevenue(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        $products =
            $report->products;

        usort(
            $products,
            static function (ProductAnalyticsProductRowData $left, ProductAnalyticsProductRowData $right): int {
                $revenueComparison =
                    $right->grossSales <=>
                    $left->grossSales;

                if (
                    $revenueComparison !==
                    0
                ) {
                    return $revenueComparison;
                }

                $quantityComparison =
                    $right->quantitySold <=>
                    $left->quantitySold;

                if (
                    $quantityComparison !==
                    0
                ) {
                    return $quantityComparison;
                }

                return strcmp(
                    $left->productName,
                    $right->productName,
                );
            },
        );

        $products =
            array_slice(
                $products,
                0,
                self::TOP_PRODUCTS_LIMIT,
            );

        return new ReportChartData(
            key: 'top_products_by_revenue',

            title: 'Top Products by Revenue',

            description: 'Highest-grossing product groups for the selected filters.',

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'gross_sales',

                    label: 'Gross Sales',

                    valueType: 'money',
                ),
            ],

            points: array_map(
                fn (
                    ProductAnalyticsProductRowData $product,
                ): ReportChartPointData => $this->productPoint(
                    $product,
                ),
                $products,
            ),
        );
    }

    private function topProductsByQuantity(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        $products =
            $report->products;

        usort(
            $products,
            static function (ProductAnalyticsProductRowData $left, ProductAnalyticsProductRowData $right): int {
                $quantityComparison =
                    $right->quantitySold <=>
                    $left->quantitySold;

                if (
                    $quantityComparison !==
                    0
                ) {
                    return $quantityComparison;
                }

                $revenueComparison =
                    $right->grossSales <=>
                    $left->grossSales;

                if (
                    $revenueComparison !==
                    0
                ) {
                    return $revenueComparison;
                }

                return strcmp(
                    $left->productName,
                    $right->productName,
                );
            },
        );

        $products =
            array_slice(
                $products,
                0,
                self::TOP_PRODUCTS_LIMIT,
            );

        return new ReportChartData(
            key: 'top_products_by_quantity',

            title: 'Top Products by Quantity',

            description: 'Best-selling product groups by unit quantity.',

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'quantity_sold',

                    label: 'Quantity Sold',

                    valueType: 'integer',
                ),
            ],

            points: array_map(
                fn (
                    ProductAnalyticsProductRowData $product,
                ): ReportChartPointData => $this->productPoint(
                    $product,
                ),
                $products,
            ),
        );
    }

    private function currentVsHistoricalRevenue(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        return new ReportChartData(
            key: 'current_vs_historical_revenue',

            title: 'Current vs Historical Product Revenue',

            description: 'Revenue split between currently linked products and historical deleted-product snapshots.',

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'gross_sales',

                    label: 'Gross Sales',

                    valueType: 'money',
                ),
            ],

            points: [
                new ReportChartPointData(
                    key: 'current',

                    label: 'Current Products',

                    values: [
                        'gross_sales' => $report
                            ->summary
                            ->currentProductRevenue,
                    ],
                ),

                new ReportChartPointData(
                    key: 'historical',

                    label: 'Historical Products',

                    values: [
                        'gross_sales' => $report
                            ->summary
                            ->historicalProductRevenue,
                    ],
                ),
            ],
        );
    }

    private function skuPerformance(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        $groups =
            [];

        foreach (
            $report->products as $product
        ) {
            $sku =
                $product->sku ??
                'No SKU';

            if (
                ! isset(
                    $groups[
                        $sku
                    ],
                )
            ) {
                $groups[
                    $sku
                ] = [
                    'gross_sales' => 0,

                    'quantity_sold' => 0,

                    'orders_count' => 0,
                ];
            }

            $groups[
                $sku
            ][
                'gross_sales'
            ] +=
                $product->grossSales;

            $groups[
                $sku
            ][
                'quantity_sold'
            ] +=
                $product->quantitySold;

            $groups[
                $sku
            ][
                'orders_count'
            ] +=
                $product->ordersCount;
        }

        uasort(
            $groups,
            static function (array $left, array $right): int {
                $revenueComparison =
                    $right[
                        'gross_sales'
                    ] <=>
                    $left[
                        'gross_sales'
                    ];

                if (
                    $revenueComparison !==
                    0
                ) {
                    return $revenueComparison;
                }

                return $right[
                    'quantity_sold'
                ] <=>
                    $left[
                        'quantity_sold'
                    ];
            },
        );

        $groups =
            array_slice(
                $groups,
                0,
                self::TOP_PRODUCTS_LIMIT,
                true,
            );

        $points =
            [];

        foreach (
            $groups as $sku => $values
        ) {
            $points[] =
                new ReportChartPointData(
                    key: $sku,

                    label: $sku,

                    values: [
                        'gross_sales' => $values[
                                'gross_sales'
                            ],

                        'quantity_sold' => $values[
                                'quantity_sold'
                            ],

                        'orders_count' => $values[
                                'orders_count'
                            ],
                    ],
                );
        }

        return new ReportChartData(
            key: 'sku_performance',

            title: 'SKU Performance',

            description: 'Top SKU groups ranked by gross sales.',

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'gross_sales',

                    label: 'Gross Sales',

                    valueType: 'money',
                ),

                new ReportChartSeriesData(
                    key: 'quantity_sold',

                    label: 'Quantity Sold',

                    valueType: 'integer',
                ),
            ],

            points: $points,
        );
    }

    private function revenueShareByProduct(
        ProductAnalyticsResultData $report,
    ): ReportChartData {
        $products =
            $report->products;

        usort(
            $products,
            static fn (
                ProductAnalyticsProductRowData $left,
                ProductAnalyticsProductRowData $right,
            ): int => $right->grossSales <=>
                $left->grossSales,
        );

        $products =
            array_slice(
                $products,
                0,
                self::TOP_PRODUCTS_LIMIT,
            );

        return new ReportChartData(
            key: 'revenue_share_by_product',

            title: 'Revenue Share by Product',

            description: 'Gross sales contribution from the highest-revenue product groups.',

            type: 'bar',

            series: [
                new ReportChartSeriesData(
                    key: 'gross_sales',

                    label: 'Gross Sales',

                    valueType: 'money',
                ),
            ],

            points: array_map(
                fn (
                    ProductAnalyticsProductRowData $product,
                ): ReportChartPointData => $this->productPoint(
                    $product,
                ),
                $products,
            ),
        );
    }

    private function productPoint(
        ProductAnalyticsProductRowData $product,
    ): ReportChartPointData {
        return new ReportChartPointData(
            key: $this->productKey(
                $product,
            ),

            label: $this->productLabel(
                $product,
            ),

            values: [
                'product_id' => $product->productId,

                'sku' => $product->sku,

                'historical' => $product->historical
                    ? 1
                    : 0,

                'quantity_sold' => $product->quantitySold,

                'orders_count' => $product->ordersCount,

                'gross_sales' => $product->grossSales,

                'average_revenue_per_item' => $product->averageRevenuePerItem,
            ],
        );
    }

    private function productKey(
        ProductAnalyticsProductRowData $product,
    ): string {
        if (
            $product->productId !==
            null
        ) {
            return 'product:'.
                $product->productId.
                ':'.
                ($product->sku ?? '');
        }

        return 'historical:'.
            sha1(
                $product->productName.
                '|'.
                ($product->sku ?? ''),
            );
    }

    private function productLabel(
        ProductAnalyticsProductRowData $product,
    ): string {
        if (
            $product->sku ===
            null ||
            $product->sku ===
            ''
        ) {
            return $product->productName;
        }

        return sprintf(
            '%s (%s)',
            $product->productName,
            $product->sku,
        );
    }
}
