<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\DashboardRecentOrderData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardRecentOrdersQuery
{
    /**
     * @return list<DashboardRecentOrderData>
     */
    public function get(
        ReportDateRangeData $range,
        int $limit = 8,
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    20,
                ),
            );

        $rows =
            DB::table(
                'orders',
            )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'orders.user_id',
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
                ->orderByDesc(
                    'orders.placed_at',
                )
                ->orderByDesc(
                    'orders.id',
                )
                ->limit(
                    $limit,
                )
                ->get([
                    'orders.id',
                    'orders.order_number',
                    'orders.status',
                    'orders.payment_status',
                    'orders.fulfilment_status',
                    'orders.currency_code',
                    'orders.grand_total',
                    'orders.placed_at',
                    'users.name AS customer_name',
                ]);

        return $rows
            ->map(
                function (stdClass $row) use ($range): DashboardRecentOrderData {
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
                                $range->timezone,
                            )
                            ->toISOString();

                    return new DashboardRecentOrderData(
                        id: (int) $row->id,

                        orderNumber: (string) $row->order_number,

                        customerName: (string) $row->customer_name,

                        status: $status->value,

                        statusLabel: $status->label(),

                        paymentStatus: $paymentStatus->value,

                        paymentStatusLabel: $paymentStatus->label(),

                        fulfilmentStatus: $fulfilmentStatus->value,

                        fulfilmentStatusLabel: $fulfilmentStatus->label(),

                        currencyCode: (string) $row->currency_code,

                        grandTotal: (int) $row->grand_total,

                        placedAt: $placedAt,
                    );
                },
            )
            ->values()
            ->all();
    }
}
