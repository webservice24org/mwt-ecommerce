<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\ProductAnalyticsProductRowData;
use App\Domain\Reporting\Data\ProductAnalyticsResultData;
use App\Domain\Reporting\Data\ProductAnalyticsSummaryData;
use App\Domain\Reporting\Data\ProductAnalyticsTrendRowData;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final class GetAdminProductAnalyticsReportQuery
{
    public function get(
        ProductPerformanceReportFiltersData $filters,
    ): ProductAnalyticsResultData {
        return new ProductAnalyticsResultData(
            summary: $this->summary(
                $filters,
            ),

            trend: $this->trend(
                $filters,
            ),

            products: $this->products(
                $filters,
            ),
        );
    }

    private function summary(
        ProductPerformanceReportFiltersData $filters,
    ): ProductAnalyticsSummaryData {
        $row =
            $this->baseQuery(
                $filters,
            )
                ->selectRaw(
                    '
                    COALESCE(SUM(order_items.quantity), 0) as quantity_sold,
                    COUNT(DISTINCT orders.id) as orders_count,
                    COALESCE(SUM(order_items.line_total), 0) as gross_sales,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN order_items.product_id IS NOT NULL
                                THEN order_items.line_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as current_product_revenue,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN order_items.product_id IS NULL
                                THEN order_items.line_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as historical_product_revenue
                    ',
                )
                ->first();

        $productGroupsCount =
            (int) DB::query()
                ->fromSub(
                    $this
                        ->groupedProductsQuery(
                            $filters,
                        )
                        ->reorder(),
                    'product_analytics_groups',
                )
                ->count();

        if (! $row instanceof stdClass) {
            return new ProductAnalyticsSummaryData(
                productGroupsCount: $productGroupsCount,
                quantitySold: 0,
                ordersCount: 0,
                grossSales: 0,
                averageRevenuePerOrder: 0,
                averageRevenuePerItem: 0,
                currentProductRevenue: 0,
                historicalProductRevenue: 0,
            );
        }

        $quantitySold =
            (int) $row->quantity_sold;

        $ordersCount =
            (int) $row->orders_count;

        $grossSales =
            (int) $row->gross_sales;

        return new ProductAnalyticsSummaryData(
            productGroupsCount: $productGroupsCount,

            quantitySold: $quantitySold,

            ordersCount: $ordersCount,

            grossSales: $grossSales,

            averageRevenuePerOrder: $ordersCount > 0
            ? intdiv(
                $grossSales,
                $ordersCount,
            )
            : 0,

            averageRevenuePerItem: $quantitySold > 0
            ? intdiv(
                $grossSales,
                $quantitySold,
            )
            : 0,

            currentProductRevenue: (int)
            $row->current_product_revenue,

            historicalProductRevenue: (int)
            $row->historical_product_revenue,
        );
    }

    /**
     * @return list<ProductAnalyticsTrendRowData>
     */
    private function trend(
        ProductPerformanceReportFiltersData $filters,
    ): array {
        $rows =
            $this->baseQuery(
                $filters,
            )
                ->selectRaw(
                    '
                    DATE(orders.placed_at) as report_date,
                    COALESCE(SUM(order_items.quantity), 0) as quantity_sold,
                    COUNT(DISTINCT orders.id) as orders_count,
                    COALESCE(SUM(order_items.line_total), 0) as gross_sales,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN order_items.product_id IS NOT NULL
                                THEN order_items.line_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as current_product_revenue,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN order_items.product_id IS NULL
                                THEN order_items.line_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as historical_product_revenue
                    ',
                )
                ->groupByRaw(
                    'DATE(orders.placed_at)',
                )
                ->orderByRaw(
                    'DATE(orders.placed_at) ASC',
                )
                ->get();

        /** @var list<ProductAnalyticsTrendRowData> $trend */
        $trend = [];

        foreach (
            $rows as $row
        ) {
            $date =
                (string)
                $row->report_date;

            $trend[] =
                new ProductAnalyticsTrendRowData(
                    date: $date,

                    label: CarbonImmutable::parse(
                        $date,
                    )->format(
                        'M j, Y',
                    ),

                    quantitySold: (int)
                    $row->quantity_sold,

                    ordersCount: (int)
                    $row->orders_count,

                    grossSales: (int)
                    $row->gross_sales,

                    currentProductRevenue: (int)
                    $row->current_product_revenue,

                    historicalProductRevenue: (int)
                    $row->historical_product_revenue,
                );
        }

        return $trend;
    }

    /**
     * @return list<ProductAnalyticsProductRowData>
     */
    private function products(
        ProductPerformanceReportFiltersData $filters,
    ): array {
        $rows =
            $this
                ->groupedProductsQuery(
                    $filters,
                )
                ->orderByDesc(
                    'quantity_sold',
                )
                ->orderByDesc(
                    'gross_sales',
                )
                ->orderBy(
                    'order_items.product_name',
                )
                ->orderBy(
                    'order_items.sku',
                )
                ->orderBy(
                    'order_items.product_id',
                )
                ->get();

        /** @var list<ProductAnalyticsProductRowData> $products */
        $products = [];

        foreach (
            $rows as $row
        ) {
            $quantitySold =
                (int)
                $row->quantity_sold;

            $grossSales =
                (int)
                $row->gross_sales;

            $productId =
                $row->product_id !==
                null
                ? (int)
                $row->product_id
                : null;

            $products[] =
                new ProductAnalyticsProductRowData(
                    productId: $productId,

                    productName: (string)
                    $row->product_name,

                    sku: $row->sku !==
                    null
                    ? (string)
                    $row->sku
                    : null,

                    historical: $productId ===
                    null,

                    quantitySold: $quantitySold,

                    ordersCount: (int)
                    $row->orders_count,

                    grossSales: $grossSales,

                    averageRevenuePerItem: $quantitySold > 0
                    ? intdiv(
                        $grossSales,
                        $quantitySold,
                    )
                    : 0,
                );
        }

        return $products;
    }

    private function groupedProductsQuery(
        ProductPerformanceReportFiltersData $filters,
    ): Builder {
        return $this
            ->baseQuery(
                $filters,
            )
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.sku',
            ])
            ->selectRaw(
                '
                COALESCE(SUM(order_items.quantity), 0) as quantity_sold,
                COUNT(DISTINCT orders.id) as orders_count,
                COALESCE(SUM(order_items.line_total), 0) as gross_sales
                ',
            )
            ->groupBy(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.sku',
            );
    }

    private function baseQuery(
        ProductPerformanceReportFiltersData $filters,
    ): Builder {
        $query =
            DB::table(
                'order_items',
            )
                ->join(
                    'orders',
                    'orders.id',
                    '=',
                    'order_items.order_id',
                )
                ->where(
                    'orders.placed_at',
                    '>=',
                    $filters
                        ->range
                        ->fromInclusive,
                )
                ->where(
                    'orders.placed_at',
                    '<',
                    $filters
                        ->range
                        ->toExclusive,
                );

        $statusValues =
            $filters
                ->statusValues();

        if (
            $statusValues !==
            []
        ) {
            $query->whereIn(
                'orders.status',
                $statusValues,
            );
        }

        $paymentStatusValues =
            $filters
                ->paymentStatusValues();

        if (
            $paymentStatusValues !==
            []
        ) {
            $query->whereIn(
                'orders.payment_status',
                $paymentStatusValues,
            );
        }

        $search =
            $filters
                ->normalizedSearch();

        if (
            $search !==
            null
        ) {
            $query->where(
                static function (Builder $query) use ($search): void {
                    $query
                        ->where(
                            'order_items.product_name',
                            'like',
                            '%'.
                            $search.
                            '%',
                        )
                        ->orWhere(
                            'order_items.sku',
                            'like',
                            '%'.
                            $search.
                            '%',
                        );
                },
            );
        }

        return $query;
    }
}
