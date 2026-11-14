<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\ProductPerformanceExportRowData;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Data\ReportExportData;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminProductPerformanceExportQuery
{
    public function get(
        ProductPerformanceReportFiltersData $filters,
    ): ReportExportData {
        $rows =
            $this->query(
                $filters,
            )
                ->get()
                ->map(
                    fn (
                        stdClass $row,
                    ): ProductPerformanceExportRowData => $this->mapRow(
                        $row,
                    ),
                )
                ->values()
                ->all();

        return new ReportExportData(
            columns: [
                'product_id',
                'product_name',
                'sku',
                'historical',
                'quantity_sold',
                'orders_count',
                'gross_sales',
            ],

            rows: $rows,
        );
    }

    private function query(
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

            /*
             * Stable deterministic export ordering.
             */
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
            );
    }

    private function mapRow(
        stdClass $row,
    ): ProductPerformanceExportRowData {
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

        return new ProductPerformanceExportRowData(
            productId: $productId,

            productName: (string) $row
                ->product_name,

            sku: $sku,

            historical: $productId === null,

            quantitySold: (int) $row
                ->quantity_sold,

            ordersCount: (int) $row
                ->orders_count,

            grossSales: (int) $row
                ->gross_sales,
        );
    }

    public function count(
        ProductPerformanceReportFiltersData $filters,
    ): int {
        $grouped =
            $this
                ->query(
                    $filters,
                )
                ->reorder();

        return (int) DB::query()
            ->fromSub(
                $grouped,
                'product_performance_export',
            )
            ->count();
    }
}
