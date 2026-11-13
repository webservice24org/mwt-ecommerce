<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\AdminDashboardMetricsData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardMetricsQuery
{
    public function get(
        ReportDateRangeData $range,
    ): AdminDashboardMetricsData {
        $orders =
            $this->ordersInRange(
                $range,
            );

        $row =
            $orders
                ->selectRaw(
                    'COUNT(*) AS orders_count',
                )
                ->selectRaw(
                    'COALESCE(SUM(subtotal), 0) AS gross_sales',
                )
                ->selectRaw(
                    'COALESCE(SUM(discount_total), 0) AS discount_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(delivery_total), 0) AS shipping_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(tax_total), 0) AS tax_total',
                )
                ->selectRaw(
                    'COALESCE(SUM(grand_total), 0) AS net_sales',
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
                    ) AS paid_orders_count
                    SQL,
                    [
                        PaymentStatus::Paid->value,
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
                    ) AS paid_total
                    SQL,
                    [
                        PaymentStatus::Paid->value,
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
                    ) AS due_orders_count
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
                    ) AS due_total
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
                    ) AS unpaid_orders_count
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
                    ) AS unpaid_total
                    SQL,
                    [
                        PaymentStatus::Unpaid->value,
                    ],
                )
                ->selectRaw(
                    'COUNT(DISTINCT user_id) AS customers_count',
                )
                ->first();

        $row =
            $row instanceof stdClass
            ? $row
            : new stdClass;

        $ordersCount =
            $this->integer(
                $row,
                'orders_count',
            );

        $netSales =
            $this->integer(
                $row,
                'net_sales',
            );

        $productsSold =
            $this->productsSold(
                $range,
            );

        return new AdminDashboardMetricsData(
            ordersCount: $ordersCount,

            grossSales: $this->integer(
                $row,
                'gross_sales',
            ),

            discountTotal: $this->integer(
                $row,
                'discount_total',
            ),

            shippingTotal: $this->integer(
                $row,
                'shipping_total',
            ),

            taxTotal: $this->integer(
                $row,
                'tax_total',
            ),

            netSales: $netSales,

            paidOrdersCount: $this->integer(
                $row,
                'paid_orders_count',
            ),

            paidTotal: $this->integer(
                $row,
                'paid_total',
            ),

            dueOrdersCount: $this->integer(
                $row,
                'due_orders_count',
            ),

            dueTotal: $this->integer(
                $row,
                'due_total',
            ),

            unpaidOrdersCount: $this->integer(
                $row,
                'unpaid_orders_count',
            ),

            unpaidTotal: $this->integer(
                $row,
                'unpaid_total',
            ),

            productsSold: $productsSold,

            customersCount: $this->integer(
                $row,
                'customers_count',
            ),

            averageOrderValue: $ordersCount > 0
            ? intdiv(
                $netSales,
                $ordersCount,
            )
            : 0,
        );
    }

    private function ordersInRange(
        ReportDateRangeData $range,
    ): Builder {
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
            );
    }

    private function productsSold(
        ReportDateRangeData $range,
    ): int {
        $quantity =
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
                ->sum(
                    'order_items.quantity',
                );

        return (int) $quantity;
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
