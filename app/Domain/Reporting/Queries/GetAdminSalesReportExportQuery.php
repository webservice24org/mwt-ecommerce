<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ReportExportData;
use App\Domain\Reporting\Data\SalesReportExportRowData;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminSalesReportExportQuery
{
    public function get(
        SalesReportFiltersData $filters,
    ): ReportExportData {
        $rows =
            $this->query(
                $filters,
            )
                ->get()
                ->map(
                    fn (
                        stdClass $row,
                    ): SalesReportExportRowData => $this->mapRow(
                        $row,
                    ),
                )
                ->values()
                ->all();

        return new ReportExportData(
            columns: [
                'order_id',
                'order_number',
                'customer_id',
                'customer_name',
                'status',
                'status_label',
                'payment_status',
                'payment_status_label',
                'fulfilment_status',
                'fulfilment_status_label',
                'currency_code',
                'subtotal',
                'discount_total',
                'delivery_total',
                'tax_total',
                'grand_total',
                'items_quantity',
                'placed_at',
            ],

            rows: $rows,
        );
    }

    private function query(
        SalesReportFiltersData $filters,
    ): Builder {
        $items =
            DB::table(
                'order_items',
            )
                ->select(
                    'order_id',
                )
                ->selectRaw(
                    'COALESCE(SUM(quantity), 0) AS items_quantity',
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
                    $items,
                    'items_summary',
                    static function ($join): void {
                        $join->on(
                            'items_summary.order_id',
                            '=',
                            'orders.id',
                        );
                    },
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
                            'orders.order_number',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'users.name',
                            'like',
                            $like,
                        );
                },
            );
        }

        return $query
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.user_id',
                'users.name AS customer_name',
                'orders.status',
                'orders.payment_status',
                'orders.fulfilment_status',
                'orders.currency_code',
                'orders.subtotal',
                'orders.discount_total',
                'orders.delivery_total',
                'orders.tax_total',
                'orders.grand_total',
                'orders.placed_at',
            ])
            ->selectRaw(
                'COALESCE(items_summary.items_quantity, 0) AS items_quantity',
            )
            ->orderByDesc(
                'orders.placed_at',
            )
            ->orderByDesc(
                'orders.id',
            );
    }

    private function mapRow(
        stdClass $row,
    ): SalesReportExportRowData {
        $status =
            OrderStatus::from(
                (string) $row->status,
            );

        $paymentStatus =
            PaymentStatus::from(
                (string) $row->payment_status,
            );

        $fulfilmentStatus =
            FulfilmentStatus::from(
                (string) $row->fulfilment_status,
            );

        return new SalesReportExportRowData(
            orderId: (int) $row->id,

            orderNumber: (string) $row
                ->order_number,

            customerId: is_numeric(
                $row->user_id,
            )
            ? (int) $row->user_id
            : null,

            customerName: is_string(
                $row->customer_name,
            )
            ? $row->customer_name
            : 'Guest',

            status: $status->value,

            statusLabel: $status->label(),

            paymentStatus: $paymentStatus->value,

            paymentStatusLabel: $paymentStatus->label(),

            fulfilmentStatus: $fulfilmentStatus->value,

            fulfilmentStatusLabel: $fulfilmentStatus->label(),

            currencyCode: (string) $row
                ->currency_code,

            subtotal: (int) $row
                ->subtotal,

            discountTotal: (int) $row
                ->discount_total,

            deliveryTotal: (int) $row
                ->delivery_total,

            taxTotal: (int) $row
                ->tax_total,

            grandTotal: (int) $row
                ->grand_total,

            itemsQuantity: (int) $row
                ->items_quantity,

            placedAt: (string) $row
                ->placed_at,
        );
    }

    public function count(
        SalesReportFiltersData $filters,
    ): int {
        return (int) $this
            ->query(
                $filters,
            )
            ->reorder()
            ->count(
                'orders.id',
            );
    }
}
