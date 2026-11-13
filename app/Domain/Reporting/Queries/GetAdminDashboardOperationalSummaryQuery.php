<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\AdminDashboardOperationalSummaryData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardOperationalSummaryQuery
{
    public function get(
        ReportDateRangeData $range,
    ): AdminDashboardOperationalSummaryData {
        $row =
            DB::table(
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
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS pending_orders_count
                    SQL,
                    [
                        OrderStatus::Pending->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS processing_orders_count
                    SQL,
                    [
                        OrderStatus::Processing->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS completed_orders_count
                    SQL,
                    [
                        OrderStatus::Completed->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fulfilment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS unfulfilled_orders_count
                    SQL,
                    [
                        FulfilmentStatus::Unfulfilled->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fulfilment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS fulfilment_processing_orders_count
                    SQL,
                    [
                        FulfilmentStatus::Processing->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fulfilment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS shipped_orders_count
                    SQL,
                    [
                        FulfilmentStatus::Shipped->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fulfilment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS delivered_orders_count
                    SQL,
                    [
                        FulfilmentStatus::Delivered->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN fulfilment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS returned_orders_count
                    SQL,
                    [
                        FulfilmentStatus::Returned->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS due_payment_orders_count
                    SQL,
                    [
                        PaymentStatus::Due->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_status = ?
                                THEN grand_total
                                ELSE 0
                            END
                        ),
                        0
                    ) AS due_payment_total
                    SQL,
                    [
                        PaymentStatus::Due->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_status = ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS unpaid_payment_orders_count
                    SQL,
                    [
                        PaymentStatus::Unpaid->value,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_status = ?
                                THEN grand_total
                                ELSE 0
                            END
                        ),
                        0
                    ) AS unpaid_payment_total
                    SQL,
                    [
                        PaymentStatus::Unpaid->value,
                    ],
                )
                ->first();

        $row =
            $row instanceof stdClass
            ? $row
            : new stdClass;

        return new AdminDashboardOperationalSummaryData(
            pendingOrdersCount: $this->integer(
                $row,
                'pending_orders_count',
            ),

            processingOrdersCount: $this->integer(
                $row,
                'processing_orders_count',
            ),

            completedOrdersCount: $this->integer(
                $row,
                'completed_orders_count',
            ),

            unfulfilledOrdersCount: $this->integer(
                $row,
                'unfulfilled_orders_count',
            ),

            fulfilmentProcessingOrdersCount: $this->integer(
                $row,
                'fulfilment_processing_orders_count',
            ),

            shippedOrdersCount: $this->integer(
                $row,
                'shipped_orders_count',
            ),

            deliveredOrdersCount: $this->integer(
                $row,
                'delivered_orders_count',
            ),

            returnedOrdersCount: $this->integer(
                $row,
                'returned_orders_count',
            ),

            duePaymentOrdersCount: $this->integer(
                $row,
                'due_payment_orders_count',
            ),

            duePaymentTotal: $this->integer(
                $row,
                'due_payment_total',
            ),

            unpaidPaymentOrdersCount: $this->integer(
                $row,
                'unpaid_payment_orders_count',
            ),

            unpaidPaymentTotal: $this->integer(
                $row,
                'unpaid_payment_total',
            ),
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
