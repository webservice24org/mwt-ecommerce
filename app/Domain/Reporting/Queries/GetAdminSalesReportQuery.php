<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\SalesReportFiltersData;
use App\Domain\Reporting\Data\SalesReportResultData;
use App\Domain\Reporting\Data\SalesReportRowData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminSalesReportQuery
{
    public function get(
        SalesReportFiltersData $filters,
        int $page = 1,
    ): SalesReportResultData {
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

        $rows =
            array_map(
                fn (
                    stdClass $row,
                ): SalesReportRowData => $this->mapRow(
                    row: $row,

                    timezone: $filters
                        ->range
                        ->timezone,
                ),
                $paginator
                    ->items(),
            );

        return new SalesReportResultData(
            rows: $rows,

            currentPage: $paginator
                ->currentPage(),

            perPage: $paginator
                ->perPage(),

            total: $paginator
                ->total(),

            lastPage: $paginator
                ->lastPage(),

            subtotal: $this->integer(
                $summary,
                'subtotal',
            ),

            discountTotal: $this->integer(
                $summary,
                'discount_total',
            ),

            deliveryTotal: $this->integer(
                $summary,
                'delivery_total',
            ),

            taxTotal: $this->integer(
                $summary,
                'tax_total',
            ),

            grandTotal: $this->integer(
                $summary,
                'grand_total',
            ),

            itemsQuantity: $this->integer(
                $summary,
                'items_quantity',
            ),
        );
    }

    private function baseQuery(
        SalesReportFiltersData $filters,
    ): Builder {
        $query =
            DB::table(
                'orders',
            )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'orders.user_id',
                )
                ->leftJoinSub(
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
                        ),
                    'item_totals',
                    'item_totals.order_id',
                    '=',
                    'orders.id',
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
                '%'.$search.'%';

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
                'COALESCE(item_totals.items_quantity, 0) AS items_quantity',
            )
            ->orderByDesc(
                'orders.placed_at',
            )
            ->orderByDesc(
                'orders.id',
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
                    'COALESCE(SUM(orders.subtotal), 0) AS subtotal',
                )
                ->selectRaw(
                    'COALESCE(SUM(orders.discount_total), 0) AS discount_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(orders.delivery_total), 0) AS delivery_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(orders.tax_total), 0) AS tax_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(orders.grand_total), 0) AS grand_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(item_totals.items_quantity), 0) AS items_quantity',
                )
                ->first();

        return $row instanceof stdClass
            ? $row
            : new stdClass;
    }

    private function mapRow(
        stdClass $row,
        string $timezone,
    ): SalesReportRowData {
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

        $placedAt =
            CarbonImmutable::parse(
                (string) $row->placed_at,
            )
                ->setTimezone(
                    $timezone,
                )
                ->toISOString();

        return new SalesReportRowData(
            id: (int) $row->id,

            orderNumber: (string) $row->order_number,

            customerId: (int) $row->user_id,

            customerName: (string) $row->customer_name,

            status: $status->value,

            statusLabel: $status->label(),

            paymentStatus: $paymentStatus->value,

            paymentStatusLabel: $paymentStatus->label(),

            fulfilmentStatus: $fulfilmentStatus->value,

            fulfilmentStatusLabel: $fulfilmentStatus->label(),

            currencyCode: (string) $row->currency_code,

            subtotal: (int) $row->subtotal,

            discountTotal: (int) $row->discount_total,

            deliveryTotal: (int) $row->delivery_total,

            taxTotal: (int) $row->tax_total,

            grandTotal: (int) $row->grand_total,

            itemsQuantity: (int) $row->items_quantity,

            placedAt: $placedAt,
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
