<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reporting\Data\DashboardTopProductData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardTopProductsQuery
{
    /**
     * @return list<DashboardTopProductData>
     */
    public function get(
        ReportDateRangeData $range,
        int $limit = 10,
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    50,
                ),
            );

        $rows =
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
                ->limit(
                    $limit,
                )
                ->get();

        return $rows
            ->map(
                static function (stdClass $row): DashboardTopProductData {
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

                    return new DashboardTopProductData(
                        productId: $productId,

                        productName: (string) $row->product_name,

                        sku: $sku,

                        quantitySold: (int) $row->quantity_sold,

                        ordersCount: (int) $row->orders_count,

                        grossSales: (int) $row->gross_sales,
                    );
                },
            )
            ->values()
            ->all();
    }
}
