<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reporting\Data\DashboardTrendPointData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardTrendQuery
{
    /**
     * @return list<DashboardTrendPointData>
     */
    public function get(
        ReportDateRangeData $range,
    ): array {
        $orderRows =
            $this->orderRows(
                $range,
            );

        $productRows =
            $this->productRows(
                $range,
            );

        $ordersByDate =
            $this->indexRows(
                $orderRows,
            );

        $productsByDate =
            $this->indexRows(
                $productRows,
            );

        $points = [];

        $cursor =
            $range
                ->fromInclusive
                ->setTimezone(
                    $range->timezone,
                )
                ->startOfDay();

        $end =
            $range
                ->toExclusive
                ->setTimezone(
                    $range->timezone,
                )
                ->startOfDay();

        while (
            $cursor->lessThan(
                $end,
            )
        ) {
            $date =
                $cursor
                    ->toDateString();

            $orderRow =
                $ordersByDate->get(
                    $date,
                );

            $productRow =
                $productsByDate->get(
                    $date,
                );

            $points[] =
                new DashboardTrendPointData(
                    date: $date,

                    ordersCount: $this->integer(
                        $orderRow,
                        'orders_count',
                    ),

                    netSales: $this->integer(
                        $orderRow,
                        'net_sales',
                    ),

                    productsSold: $this->integer(
                        $productRow,
                        'products_sold',
                    ),
                );

            $cursor =
                $cursor->addDay();
        }

        return $points;
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function orderRows(
        ReportDateRangeData $range,
    ): Collection {
        return DB::table(
            'orders',
        )
            ->whereNotNull(
                'placed_at',
            )
            ->where(
                'placed_at',
                '>=',
                $range->fromInclusive,
            )
            ->where(
                'placed_at',
                '<',
                $range->toExclusive,
            )
            ->where(
                'status',
                '!=',
                OrderStatus::Cancelled->value,
            )
            ->selectRaw(
                'DATE(placed_at) AS report_date',
            )
            ->selectRaw(
                'COUNT(*) AS orders_count',
            )
            ->selectRaw(
                'COALESCE(SUM(grand_total), 0) AS net_sales',
            )
            ->groupByRaw(
                'DATE(placed_at)',
            )
            ->orderByRaw(
                'DATE(placed_at)',
            )
            ->get();
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function productRows(
        ReportDateRangeData $range,
    ): Collection {
        return DB::table(
            'order_items',
        )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id',
            )
            ->whereNotNull(
                'orders.placed_at',
            )
            ->where(
                'orders.placed_at',
                '>=',
                $range->fromInclusive,
            )
            ->where(
                'orders.placed_at',
                '<',
                $range->toExclusive,
            )
            ->where(
                'orders.status',
                '!=',
                OrderStatus::Cancelled->value,
            )
            ->selectRaw(
                'DATE(orders.placed_at) AS report_date',
            )
            ->selectRaw(
                'COALESCE(SUM(order_items.quantity), 0) AS products_sold',
            )
            ->groupByRaw(
                'DATE(orders.placed_at)',
            )
            ->orderByRaw(
                'DATE(orders.placed_at)',
            )
            ->get();
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     * @return Collection<string, stdClass>
     */
    private function indexRows(
        Collection $rows,
    ): Collection {
        return $rows
            ->filter(
                static fn (
                    stdClass $row,
                ): bool => property_exists(
                    $row,
                    'report_date',
                )
                    && is_string(
                        $row->report_date,
                    ),
            )
            ->keyBy(
                static fn (
                    stdClass $row,
                ): string => $row->report_date,
            );
    }

    private function integer(
        mixed $row,
        string $property,
    ): int {
        if (
            ! $row instanceof stdClass
            || ! property_exists(
                $row,
                $property,
            )
        ) {
            return 0;
        }

        $value =
            $row->{$property};

        if (
            ! is_int(
                $value,
            )
            && ! is_float(
                $value,
            )
            && ! is_string(
                $value,
            )
        ) {
            return 0;
        }

        if (
            ! is_numeric(
                $value,
            )
        ) {
            return 0;
        }

        return (int) $value;
    }
}
