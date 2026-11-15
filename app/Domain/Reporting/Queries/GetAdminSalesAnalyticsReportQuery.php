<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\SalesAnalyticsBreakdownRowData;
use App\Domain\Reporting\Data\SalesAnalyticsResultData;
use App\Domain\Reporting\Data\SalesAnalyticsSummaryData;
use App\Domain\Reporting\Data\SalesAnalyticsTrendRowData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final class GetAdminSalesAnalyticsReportQuery
{
    public function get(
        SalesReportFiltersData $filters,
    ): SalesAnalyticsResultData {
        return new SalesAnalyticsResultData(
            summary: $this->summary(
                $filters,
            ),

            trend: $this->trend(
                $filters,
            ),

            statusBreakdown: $this->breakdown(
                filters: $filters,
                column: 'orders.status',
            ),

            paymentStatusBreakdown: $this->breakdown(
                filters: $filters,
                column: 'orders.payment_status',
            ),

            paymentMethodBreakdown: $this->breakdown(
                filters: $filters,
                column: 'orders.payment_method',
            ),
        );
    }

    private function summary(
        SalesReportFiltersData $filters,
    ): SalesAnalyticsSummaryData {
        $row =
            $this->baseQuery(
                $filters,
            )
                ->selectRaw(
                    '
                    COUNT(orders.id) as orders_count,
                    COALESCE(SUM(order_item_totals.items_quantity), 0) as items_quantity,
                    COALESCE(SUM(orders.subtotal), 0) as subtotal,
                    COALESCE(SUM(orders.discount_total), 0) as discount_total,
                    COALESCE(SUM(orders.delivery_total), 0) as delivery_total,
                    COALESCE(SUM(orders.tax_total), 0) as tax_total,
                    COALESCE(SUM(orders.grand_total), 0) as grand_total,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN orders.payment_status = ?
                                THEN orders.grand_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as paid_revenue
                    ',
                    [
                        PaymentStatus::Paid->value,
                    ],
                )
                ->first();

        if (! $row instanceof stdClass) {
            return new SalesAnalyticsSummaryData(
                ordersCount: 0,
                itemsQuantity: 0,
                subtotal: 0,
                discountTotal: 0,
                deliveryTotal: 0,
                taxTotal: 0,
                grandTotal: 0,
                paidRevenue: 0,
                averageOrderValue: 0,
            );
        }

        $ordersCount =
            (int) $row->orders_count;

        $grandTotal =
            (int) $row->grand_total;

        return new SalesAnalyticsSummaryData(
            ordersCount: $ordersCount,

            itemsQuantity: (int) $row->items_quantity,

            subtotal: (int) $row->subtotal,

            discountTotal: (int) $row->discount_total,

            deliveryTotal: (int) $row->delivery_total,

            taxTotal: (int) $row->tax_total,

            grandTotal: $grandTotal,

            paidRevenue: (int) $row->paid_revenue,

            averageOrderValue: $ordersCount > 0
            ? intdiv(
                $grandTotal,
                $ordersCount,
            )
            : 0,
        );
    }

    /**
     * @return list<SalesAnalyticsTrendRowData>
     */
    private function trend(
        SalesReportFiltersData $filters,
    ): array {
        $rows =
            $this->baseQuery(
                $filters,
            )
                ->selectRaw(
                    '
                    DATE(orders.placed_at) as report_date,
                    COUNT(orders.id) as orders_count,
                    COALESCE(SUM(order_item_totals.items_quantity), 0) as items_quantity,
                    COALESCE(SUM(orders.subtotal), 0) as subtotal,
                    COALESCE(SUM(orders.discount_total), 0) as discount_total,
                    COALESCE(SUM(orders.delivery_total), 0) as delivery_total,
                    COALESCE(SUM(orders.tax_total), 0) as tax_total,
                    COALESCE(SUM(orders.grand_total), 0) as grand_total,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN orders.payment_status = ?
                                THEN orders.grand_total
                                ELSE 0
                            END
                        ),
                        0
                    ) as paid_revenue
                    ',
                    [
                        PaymentStatus::Paid->value,
                    ],
                )
                ->groupByRaw(
                    'DATE(orders.placed_at)',
                )
                ->orderByRaw(
                    'DATE(orders.placed_at) ASC',
                )
                ->get();

        /** @var list<SalesAnalyticsTrendRowData> $trend */
        $trend = [];

        foreach (
            $rows as $row
        ) {
            $date =
                (string)
                $row->report_date;

            $trend[] =
                new SalesAnalyticsTrendRowData(
                    date: $date,

                    label: CarbonImmutable::parse(
                        $date,
                    )->format(
                        'M j, Y',
                    ),

                    ordersCount: (int)
                    $row->orders_count,

                    itemsQuantity: (int)
                    $row->items_quantity,

                    subtotal: (int)
                    $row->subtotal,

                    discountTotal: (int)
                    $row->discount_total,

                    deliveryTotal: (int)
                    $row->delivery_total,

                    taxTotal: (int)
                    $row->tax_total,

                    grandTotal: (int)
                    $row->grand_total,

                    paidRevenue: (int)
                    $row->paid_revenue,
                );
        }

        return $trend;
    }

    /**
     * @return list<SalesAnalyticsBreakdownRowData>
     */
    private function breakdown(
        SalesReportFiltersData $filters,
        string $column,
    ): array {
        $allowedColumns = [
            'orders.status',
            'orders.payment_status',
            'orders.payment_method',
        ];

        if (
            ! in_array(
                $column,
                $allowedColumns,
                true,
            )
        ) {
            return [];
        }

        $rows =
            $this->baseQuery(
                $filters,
            )
                ->selectRaw(
                    $column.
                    ' as breakdown_key',
                )
                ->selectRaw(
                    '
                    COUNT(orders.id) as orders_count,
                    COALESCE(SUM(orders.grand_total), 0) as grand_total
                    ',
                )
                ->groupBy(
                    $column,
                )
                ->orderByDesc(
                    'grand_total',
                )
                ->orderBy(
                    $column,
                )
                ->get();

        /** @var list<SalesAnalyticsBreakdownRowData> $result */
        $result = [];

        foreach (
            $rows as $row
        ) {
            $key =
                (string)
                $row->breakdown_key;

            $result[] =
                new SalesAnalyticsBreakdownRowData(
                    key: $key,

                    label: $this->humanize(
                        $key,
                    ),

                    ordersCount: (int)
                    $row->orders_count,

                    grandTotal: (int)
                    $row->grand_total,
                );
        }

        return $result;
    }

    private function baseQuery(
        SalesReportFiltersData $filters,
    ): Builder {
        $itemTotals =
            DB::table(
                'order_items',
            )
                ->select(
                    'order_id',
                )
                ->selectRaw(
                    'SUM(quantity) as items_quantity',
                )
                ->groupBy(
                    'order_id',
                );

        $query =
            DB::table(
                'orders',
            )
                ->leftJoin(
                    'users',
                    'users.id',
                    '=',
                    'orders.user_id',
                )
                ->leftJoinSub(
                    $itemTotals,
                    'order_item_totals',
                    static function ($join): void {
                        $join->on(
                            'order_item_totals.order_id',
                            '=',
                            'orders.id',
                        );
                    },
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
            $filters->statusValues();

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
                            'orders.order_number',
                            'like',
                            '%'.
                            $search.
                            '%',
                        )
                        ->orWhere(
                            'users.name',
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

    private function humanize(
        string $value,
    ): string {
        return ucwords(
            str_replace(
                [
                    '_',
                    '-',
                ],
                ' ',
                $value,
            ),
        );
    }
}
