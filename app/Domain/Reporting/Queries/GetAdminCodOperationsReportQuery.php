<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\CodOperationRowData;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Data\CodOperationsReportResultData;
use App\Domain\Reporting\Data\CodOperationsSummaryData;
use App\Domain\Reporting\Data\ReportPaginationData;
use App\Domain\Reporting\Operations\CodCollectionRiskClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class GetAdminCodOperationsReportQuery
{
    public function __construct(
        private readonly CodCollectionRiskClassifier $collectionRiskClassifier,
    ) {}

    public function get(
        CodOperationsReportFiltersData $filters,
        int $page = 1,
    ): CodOperationsReportResultData {
        $asOf = CarbonImmutable::now(
            $filters->range->timezone,
        );

        $baseQuery = $this->baseQuery(
            $filters,
        );

        $summary = $this->summary(
            query: clone $baseQuery,
            asOf: $asOf,
        );

        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator = $baseQuery
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.user_id',
                'users.name as customer_name',
                'orders.status',
                'orders.fulfilment_status',
                'orders.payment_method',
                'orders.payment_status',
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
                perPage: $filters->normalizedPerPage(),
                page: max(
                    1,
                    $page,
                ),
            );

        $rows = array_map(
            fn (
                object $row,
            ): CodOperationRowData => $this->mapRow(
                row: $row,
                asOf: $asOf,
            ),
            $paginator->items(),
        );

        return new CodOperationsReportResultData(
            summary: $summary,
            rows: $rows,
            pagination: new ReportPaginationData(
                currentPage: $paginator->currentPage(),
                lastPage: $paginator->lastPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
                nextPageUrl: $paginator->nextPageUrl(),
                previousPageUrl: $paginator->previousPageUrl(),
            ),
        );
    }

    private function baseQuery(
        CodOperationsReportFiltersData $filters,
    ): Builder {
        $query = DB::table(
            'orders',
        )
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'orders.user_id',
            )
            ->where(
                'orders.payment_method',
                PaymentMethod::CashOnDelivery->value,
            )
            ->where(
                'orders.placed_at',
                '>=',
                $filters->range->fromInclusive,
            )
            ->where(
                'orders.placed_at',
                '<',
                $filters->range->toExclusive,
            );

        $statuses = $filters->statusValues();

        if ($statuses !== []) {
            $query->whereIn(
                'orders.status',
                $statuses,
            );
        }

        $fulfilmentStatuses =
            $filters->fulfilmentStatusValues();

        if ($fulfilmentStatuses !== []) {
            $query->whereIn(
                'orders.fulfilment_status',
                $fulfilmentStatuses,
            );
        }

        $paymentStatuses =
            $filters->paymentStatusValues();

        if ($paymentStatuses !== []) {
            $query->whereIn(
                'orders.payment_status',
                $paymentStatuses,
            );
        }

        $search = $filters->normalizedSearch();

        if ($search !== null) {
            $query->where(
                static function (Builder $searchQuery) use ($search): void {
                    $like = '%'.$search.'%';

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
    ): CodOperationsSummaryData {
        $row = (clone $query)
            ->selectRaw(
                '
                COUNT(orders.id) as orders_count,
                COUNT(orders.id) as cod_orders_count,
                SUM(CASE WHEN orders.payment_status = ? THEN 1 ELSE 0 END) as paid_orders_count,
                SUM(CASE WHEN orders.payment_status = ? THEN orders.grand_total ELSE 0 END) as paid_total,
                SUM(CASE WHEN orders.payment_status = ? THEN 1 ELSE 0 END) as due_orders_count,
                SUM(CASE WHEN orders.payment_status = ? THEN orders.grand_total ELSE 0 END) as due_total,
                SUM(CASE WHEN orders.payment_status = ? THEN 1 ELSE 0 END) as unpaid_orders_count,
                SUM(CASE WHEN orders.payment_status = ? THEN orders.grand_total ELSE 0 END) as unpaid_total,
                SUM(CASE WHEN orders.fulfilment_status = ? AND orders.payment_status = ? THEN 1 ELSE 0 END) as delivered_due_orders_count,
                SUM(CASE WHEN orders.fulfilment_status = ? AND orders.payment_status = ? THEN orders.grand_total ELSE 0 END) as delivered_due_total,
                SUM(CASE WHEN orders.fulfilment_status = ? AND orders.payment_status = ? THEN 1 ELSE 0 END) as delivered_unpaid_orders_count,
                SUM(CASE WHEN orders.fulfilment_status = ? AND orders.payment_status = ? THEN orders.grand_total ELSE 0 END) as delivered_unpaid_total
                ',
                [
                    PaymentStatus::Paid->value,
                    PaymentStatus::Paid->value,
                    PaymentStatus::Due->value,
                    PaymentStatus::Due->value,
                    PaymentStatus::Unpaid->value,
                    PaymentStatus::Unpaid->value,
                    FulfilmentStatus::Delivered->value,
                    PaymentStatus::Due->value,
                    FulfilmentStatus::Delivered->value,
                    PaymentStatus::Due->value,
                    FulfilmentStatus::Delivered->value,
                    PaymentStatus::Unpaid->value,
                    FulfilmentStatus::Delivered->value,
                    PaymentStatus::Unpaid->value,
                ],
            )
            ->first();

        $riskRows = (clone $query)
            ->select([
                'orders.payment_status',
                'orders.fulfilment_status',
                'orders.payment_due_at',
                'orders.placed_at',
            ])
            ->get();

        $followUpOrdersCount = 0;
        $criticalRiskOrdersCount = 0;
        $highRiskOrdersCount = 0;

        foreach ($riskRows as $riskRow) {
            $risk = $this
                ->collectionRiskClassifier
                ->classify(
                    paymentStatus: (string) $riskRow->payment_status,
                    fulfilmentStatus: (string) $riskRow->fulfilment_status,
                    placedAt: CarbonImmutable::parse(
                        (string) $riskRow->placed_at,
                        $asOf->getTimezone(),
                    ),
                    paymentDueAt: $riskRow->payment_due_at !== null
                    ? CarbonImmutable::parse(
                        (string) $riskRow->payment_due_at,
                        $asOf->getTimezone(),
                    )
                    : null,
                    asOf: $asOf,
                );

            if ($risk->requiresFollowUp()) {
                $followUpOrdersCount++;
            }

            if (
                $risk
                    ->state
                    ->severity() ===
                'critical'
            ) {
                $criticalRiskOrdersCount++;
            }

            if (
                $risk
                    ->state
                    ->severity() ===
                'high'
            ) {
                $highRiskOrdersCount++;
            }
        }

        return new CodOperationsSummaryData(
            ordersCount: (int) ($row->orders_count ?? 0),
            codOrdersCount: (int) ($row->cod_orders_count ?? 0),
            paidOrdersCount: (int) ($row->paid_orders_count ?? 0),
            paidTotal: (int) ($row->paid_total ?? 0),
            dueOrdersCount: (int) ($row->due_orders_count ?? 0),
            dueTotal: (int) ($row->due_total ?? 0),
            unpaidOrdersCount: (int) ($row->unpaid_orders_count ?? 0),
            unpaidTotal: (int) ($row->unpaid_total ?? 0),
            deliveredDueOrdersCount: (int) ($row->delivered_due_orders_count ?? 0),
            deliveredDueTotal: (int) ($row->delivered_due_total ?? 0),
            deliveredUnpaidOrdersCount: (int) ($row->delivered_unpaid_orders_count ?? 0),
            deliveredUnpaidTotal: (int) ($row->delivered_unpaid_total ?? 0),
            followUpOrdersCount: $followUpOrdersCount,
            criticalRiskOrdersCount: $criticalRiskOrdersCount,
            highRiskOrdersCount: $highRiskOrdersCount,
        );
    }

    private function mapRow(
        object $row,
        CarbonImmutable $asOf,
    ): CodOperationRowData {
        $paymentStatus =
            (string) $row->payment_status;

        $grandTotal =
            (int) $row->grand_total;

        $placedAt =
            CarbonImmutable::parse(
                (string) $row->placed_at,
                $asOf->getTimezone(),
            );

        $paymentDueAt =
            $row->payment_due_at !== null
            ? CarbonImmutable::parse(
                (string) $row->payment_due_at,
                $asOf->getTimezone(),
            )
            : null;

        $ageHours =
            max(
                0,
                (int) floor(
                    $placedAt->diffInHours(
                        $asOf,
                    ),
                ),
            );

        $collectionRisk =
            $this
                ->collectionRiskClassifier
                ->classify(
                    paymentStatus: $paymentStatus,
                    fulfilmentStatus: (string) $row->fulfilment_status,
                    placedAt: $placedAt,
                    paymentDueAt: $paymentDueAt,
                    asOf: $asOf,
                );

        return new CodOperationRowData(
            orderId: (int) $row->id,
            orderNumber: (string) $row->order_number,
            customerId: $row->user_id !== null
            ? (int) $row->user_id
            : null,
            customerName: $row->customer_name !== null
            ? (string) $row->customer_name
            : 'Guest',
            status: (string) $row->status,
            fulfilmentStatus: (string) $row->fulfilment_status,
            paymentMethod: (string) $row->payment_method,
            paymentStatus: $paymentStatus,
            currencyCode: (string) $row->currency_code,
            grandTotal: $grandTotal,
            amountDue: $paymentStatus ===
            PaymentStatus::Paid->value
            ? 0
            : $grandTotal,
            paymentDueAt: $paymentDueAt?->toISOString(),
            paidAt: $row->paid_at !== null
            ? CarbonImmutable::parse(
                (string) $row->paid_at,
                $asOf->getTimezone(),
            )->toISOString()
            : null,
            placedAt: $placedAt->toISOString(),
            ageHours: $ageHours,
            ageDays: intdiv(
                $ageHours,
                24,
            ),
            collectionRisk: $collectionRisk,
        );
    }
}
