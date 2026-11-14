<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\ProductPerformanceReportResultData;
use App\Domain\Reporting\Data\ProductPerformanceReportRowData;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminProductPerformanceReportQuery
{
    public function get(
        ProductPerformanceReportFiltersData $filters,
        int $page = 1,
    ): ProductPerformanceReportResultData {
        $page =
            max(
                1,
                $page,
            );

        $perPage =
            $filters
                ->normalizedPerPage();

        $baseQuery =
            $this->baseQuery(
                $filters,
            );

        $summary =
            $this->summary(
                clone $baseQuery,
            );

        $paginator =
            $this->paginate(
                query: clone $baseQuery,

                page: $page,

                perPage: $perPage,
            );

        $rows = [];

        foreach (
            $paginator->items() as $row
        ) {
            $rows[] =
                $this->mapRow(
                    $row,
                );
        }

        return new ProductPerformanceReportResultData(
            rows: $rows,

            currentPage: $paginator
                ->currentPage(),

            perPage: $paginator
                ->perPage(),

            total: $paginator
                ->total(),

            lastPage: $paginator
                ->lastPage(),

            productGroupsCount: $paginator
                ->total(),

            quantitySold: $this->integer(
                $summary,
                'quantity_sold',
            ),

            ordersCount: $this->integer(
                $summary,
                'orders_count',
            ),

            grossSales: $this->integer(
                $summary,
                'gross_sales',
            ),
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
                ->whereNotNull(
                    'orders.placed_at',
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

        $statuses =
            $filters
                ->statusValues();

        if (
            $statuses !== []
        ) {
            $query->whereIn(
                'orders.status',
                $statuses,
            );
        }

        $paymentStatuses =
            $filters
                ->paymentStatusValues();

        if (
            $paymentStatuses !== []
        ) {
            $query->whereIn(
                'orders.payment_status',
                $paymentStatuses,
            );
        }

        $search =
            $filters
                ->normalizedSearch();

        if (
            $search !== null
        ) {
            $like =
                '%'.
                $search.
                '%';

            $query->where(
                static function (Builder $searchQuery) use ($like): void {
                    $searchQuery
                        ->where(
                            'order_items.product_name',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'order_items.sku',
                            'like',
                            $like,
                        );
                },
            );
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, stdClass>
     */
    private function paginate(
        Builder $query,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        return $query
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.sku',
            ])
            ->selectRaw(
                'COALESCE(SUM(order_items.quantity), 0) AS quantity_sold',
            )
            ->selectRaw(
                'COUNT(DISTINCT order_items.order_id) AS orders_count',
            )
            ->selectRaw(
                'COALESCE(SUM(order_items.line_total), 0) AS gross_sales',
            )
            ->groupBy(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.sku',
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
            ->paginate(
                perPage: $perPage,

                page: $page,
            );
    }

    private function summary(
        Builder $query,
    ): stdClass {
        $row =
            $query
                ->selectRaw(
                    'COALESCE(SUM(order_items.quantity), 0) AS quantity_sold',
                )
                ->selectRaw(
                    'COUNT(DISTINCT order_items.order_id) AS orders_count',
                )
                ->selectRaw(
                    'COALESCE(SUM(order_items.line_total), 0) AS gross_sales',
                )
                ->first();

        return $row instanceof stdClass
            ? $row
            : new stdClass;
    }

    private function mapRow(
        stdClass $row,
    ): ProductPerformanceReportRowData {
        $productId =
            is_numeric(
                $row->product_id,
            )
            ? (int) $row->product_id
            : null;

        $sku =
            is_string(
                $row->sku,
            )
            && trim(
                $row->sku,
            ) !== ''
            ? $row->sku
            : null;

        return new ProductPerformanceReportRowData(
            productId: $productId,

            productName: (string) $row
                ->product_name,

            sku: $sku,

            quantitySold: (int) $row
                ->quantity_sold,

            ordersCount: (int) $row
                ->orders_count,

            grossSales: (int) $row
                ->gross_sales,
        );
    }

    private function integer(
        stdClass $row,
        string $property,
    ): int {
        if (
            ! property_exists(
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
