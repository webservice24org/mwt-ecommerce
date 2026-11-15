<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\OperationalOrderRowData;
use App\Domain\Reporting\Data\OperationalOrdersReportFiltersData;
use App\Domain\Reporting\Data\OperationalOrdersReportResultData;
use App\Domain\Reporting\Data\OperationalOrdersSummaryData;
use App\Domain\Reporting\Data\ReportPaginationData;
use App\Domain\Reporting\Operations\OperationalOrderAttentionClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class GetAdminOperationalOrdersReportQuery
{
    public function __construct(
        private readonly OperationalOrderAttentionClassifier $attentionClassifier,
    ) {}

    public function get(
        OperationalOrdersReportFiltersData $filters,
        int $page = 1,
    ): OperationalOrdersReportResultData {
        $asOf =
            CarbonImmutable::now(
                $filters
                    ->range
                    ->timezone,
            );

        $baseQuery =
            $this->baseQuery(
                $filters,
            );

        $summary =
            $this->summary(
                query: clone $baseQuery,

                asOf: $asOf,
            );

        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator =
            $baseQuery
                ->select([
                    'orders.id',
                    'orders.order_number',
                    'orders.user_id',
                    'users.name as customer_name',
                    'orders.status',
                    'orders.payment_status',
                    'orders.payment_method',
                    'orders.fulfilment_status',
                    'orders.currency_code',
                    'orders.grand_total',
                    'orders.payment_due_at',
                    'orders.paid_at',
                    'orders.placed_at',
                ])
                ->orderByDesc(
                    'orders.placed_at',
                )
                ->orderByDesc(
                    'orders.id',
                )
                ->paginate(
                    perPage: $filters
                        ->normalizedPerPage(),

                    page: max(
                        1,
                        $page,
                    ),
                );

        $rows =
            array_map(
                fn (
                    object $row,
                ): OperationalOrderRowData => $this->mapRow(
                    row: $row,

                    asOf: $asOf,
                ),

                $paginator
                    ->items(),
            );

        return new OperationalOrdersReportResultData(
            summary: $summary,

            rows: $rows,

            pagination: new ReportPaginationData(
                currentPage: $paginator
                    ->currentPage(),

                lastPage: $paginator
                    ->lastPage(),

                perPage: $paginator
                    ->perPage(),

                total: $paginator
                    ->total(),

                nextPageUrl: $paginator
                    ->nextPageUrl(),

                previousPageUrl: $paginator
                    ->previousPageUrl(),
            ),
        );
    }

    private function baseQuery(
        OperationalOrdersReportFiltersData $filters,
    ): Builder {
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

        $fulfilmentStatuses =
            $filters
                ->fulfilmentStatusValues();

        if (
            $fulfilmentStatuses !== []
        ) {
            $query->whereIn(
                'orders.fulfilment_status',
                $fulfilmentStatuses,
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

        $paymentMethods =
            $filters
                ->paymentMethodValues();

        if (
            $paymentMethods !== []
        ) {
            $query->whereIn(
                'orders.payment_method',
                $paymentMethods,
            );
        }

        $search =
            $filters
                ->normalizedSearch();

        if (
            $search !== null
        ) {
            $query->where(
                static function (Builder $searchQuery) use ($search): void {
                    $like =
                        '%'.$search.'%';

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

    private function summary(
        Builder $query,
        CarbonImmutable $asOf,
    ): OperationalOrdersSummaryData {
        $row =
            (clone $query)
                ->selectRaw(
                    '
                    COUNT(orders.id) as orders_count,

                    SUM(
                        CASE
                            WHEN orders.status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as pending_orders,

                    SUM(
                        CASE
                            WHEN orders.status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as processing_orders,

                    SUM(
                        CASE
                            WHEN orders.status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as completed_orders,

                    SUM(
                        CASE
                            WHEN orders.fulfilment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as unfulfilled_orders,

                    SUM(
                        CASE
                            WHEN orders.fulfilment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as fulfilment_processing_orders,

                    SUM(
                        CASE
                            WHEN orders.fulfilment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as shipped_orders,

                    SUM(
                        CASE
                            WHEN orders.fulfilment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as delivered_orders,

                    SUM(
                        CASE
                            WHEN orders.fulfilment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as returned_orders,

                    SUM(
                        CASE
                            WHEN orders.payment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as due_payment_orders,

                    SUM(
                        CASE
                            WHEN orders.payment_status = ?
                            THEN orders.grand_total
                            ELSE 0
                        END
                    ) as due_payment_total,

                    SUM(
                        CASE
                            WHEN orders.payment_status = ?
                            THEN 1
                            ELSE 0
                        END
                    ) as unpaid_payment_orders,

                    SUM(
                        CASE
                            WHEN orders.payment_status = ?
                            THEN orders.grand_total
                            ELSE 0
                        END
                    ) as unpaid_payment_total
                    ',
                    [
                        OrderStatus::Pending->value,
                        OrderStatus::Processing->value,
                        OrderStatus::Completed->value,

                        FulfilmentStatus::Unfulfilled->value,
                        FulfilmentStatus::Processing->value,
                        FulfilmentStatus::Shipped->value,
                        FulfilmentStatus::Delivered->value,
                        FulfilmentStatus::Returned->value,

                        PaymentStatus::Due->value,
                        PaymentStatus::Due->value,

                        PaymentStatus::Unpaid->value,
                        PaymentStatus::Unpaid->value,
                    ],
                )
                ->first();

        $attentionRows =
            (clone $query)
                ->select([
                    'orders.status',
                    'orders.payment_status',
                    'orders.fulfilment_status',
                    'orders.payment_due_at',
                    'orders.placed_at',
                ])
                ->get();

        $attentionRequiredOrders =
            0;

        $criticalAttentionOrders =
            0;

        foreach (
            $attentionRows as $attentionRow
        ) {
            $attention =
                $this
                    ->attentionClassifier
                    ->classify(
                        status: (string) $attentionRow
                            ->status,

                        paymentStatus: (string) $attentionRow
                            ->payment_status,

                        fulfilmentStatus: (string) $attentionRow
                            ->fulfilment_status,

                        placedAt: CarbonImmutable::parse(
                            (string) $attentionRow
                                ->placed_at,
                            $asOf->getTimezone(),
                        ),

                        paymentDueAt: $attentionRow
                            ->payment_due_at !==
                        null
                        ? CarbonImmutable::parse(
                            (string) $attentionRow
                                ->payment_due_at,
                        )
                        : null,

                        asOf: $asOf,
                    );

            if (
                $attention
                    ->requiresAttention()
            ) {
                $attentionRequiredOrders++;
            }

            if (
                $attention
                    ->state
                    ->severity() ===
                'critical'
            ) {
                $criticalAttentionOrders++;
            }
        }

        return new OperationalOrdersSummaryData(
            ordersCount: (int) (
                $row
                    ->orders_count ??
                0
            ),

            pendingOrders: (int) (
                $row
                    ->pending_orders ??
                0
            ),

            processingOrders: (int) (
                $row
                    ->processing_orders ??
                0
            ),

            completedOrders: (int) (
                $row
                    ->completed_orders ??
                0
            ),

            unfulfilledOrders: (int) (
                $row
                    ->unfulfilled_orders ??
                0
            ),

            fulfilmentProcessingOrders: (int) (
                $row
                    ->fulfilment_processing_orders ??
                0
            ),

            shippedOrders: (int) (
                $row
                    ->shipped_orders ??
                0
            ),

            deliveredOrders: (int) (
                $row
                    ->delivered_orders ??
                0
            ),

            returnedOrders: (int) (
                $row
                    ->returned_orders ??
                0
            ),

            duePaymentOrders: (int) (
                $row
                    ->due_payment_orders ??
                0
            ),

            duePaymentTotal: (int) (
                $row
                    ->due_payment_total ??
                0
            ),

            unpaidPaymentOrders: (int) (
                $row
                    ->unpaid_payment_orders ??
                0
            ),

            unpaidPaymentTotal: (int) (
                $row
                    ->unpaid_payment_total ??
                0
            ),

            attentionRequiredOrders: $attentionRequiredOrders,

            criticalAttentionOrders: $criticalAttentionOrders,
        );
    }

    private function mapRow(
        object $row,
        CarbonImmutable $asOf,
    ): OperationalOrderRowData {
        $paymentStatus =
            (string) $row
                ->payment_status;

        $grandTotal =
            (int) $row
                ->grand_total;

        $placedAt =
            CarbonImmutable::parse(
                (string) $row
                    ->placed_at,
                $asOf->getTimezone(),
            );

        $paymentDueAt =
            $row->payment_due_at !== null
            ? CarbonImmutable::parse(
                (string) $row
                    ->payment_due_at,
                $asOf->getTimezone(),
            )
            : null;

        $ageHours =
            max(
                0,
                (int) floor(
                    $placedAt
                        ->diffInHours(
                            $asOf,
                        ),
                ),
            );

        $attention =
            $this
                ->attentionClassifier
                ->classify(
                    status: (string) $row
                        ->status,

                    paymentStatus: $paymentStatus,

                    fulfilmentStatus: (string) $row
                        ->fulfilment_status,

                    placedAt: $placedAt,

                    paymentDueAt: $paymentDueAt,

                    asOf: $asOf,
                );

        return new OperationalOrderRowData(
            orderId: (int) $row->id,

            orderNumber: (string) $row
                ->order_number,

            customerId: $row->user_id !== null
            ? (int) $row
                ->user_id
            : null,

            customerName: $row->customer_name !== null
            ? (string) $row
                ->customer_name
            : 'Guest',

            status: (string) $row
                ->status,

            paymentStatus: $paymentStatus,

            paymentMethod: (string) $row
                ->payment_method,

            fulfilmentStatus: (string) $row
                ->fulfilment_status,

            currencyCode: (string) $row
                ->currency_code,

            grandTotal: $grandTotal,

            amountDue: $paymentStatus ===
            PaymentStatus::Paid->value
            ? 0
            : $grandTotal,

            paymentDueAt: $paymentDueAt
                ?->toISOString(),

            paidAt: $row->paid_at !== null
            ? CarbonImmutable::parse(
                (string) $row
                    ->paid_at,
                $asOf->getTimezone(),
            )->toISOString()
            : null,

            placedAt: $placedAt
                ->toISOString(),

            ageHours: $ageHours,

            ageDays: intdiv(
                $ageHours,
                24,
            ),

            attention: $attention,
        );
    }
}
