<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\AdminDashboardBreakdownsData;
use App\Domain\Reporting\Data\DashboardBreakdownItemData;
use App\Domain\Reporting\Data\ReportDateRangeData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardBreakdownsQuery
{
    public function get(
        ReportDateRangeData $range,
    ): AdminDashboardBreakdownsData {
        return new AdminDashboardBreakdownsData(
            payment: $this->paymentBreakdown(
                $range,
            ),

            fulfilment: $this->fulfilmentBreakdown(
                $range,
            ),
        );
    }

    /**
     * @return list<DashboardBreakdownItemData>
     */
    private function paymentBreakdown(
        ReportDateRangeData $range,
    ): array {
        $rows =
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
                ->select(
                    'payment_status',
                )
                ->selectRaw(
                    'COUNT(*) AS orders_count',
                )
                ->selectRaw(
                    'COALESCE(SUM(grand_total), 0) AS total',
                )
                ->groupBy(
                    'payment_status',
                )
                ->get();

        $indexed =
            $this->indexRows(
                rows: $rows,

                property: 'payment_status',
            );

        return array_map(
            function (PaymentStatus $status) use ($indexed): DashboardBreakdownItemData {
                $row =
                    $indexed->get(
                        $status->value,
                    );

                return new DashboardBreakdownItemData(
                    value: $status->value,

                    label: $status->label(),

                    ordersCount: $this->integer(
                        $row,
                        'orders_count',
                    ),

                    total: $this->integer(
                        $row,
                        'total',
                    ),
                );
            },
            PaymentStatus::cases(),
        );
    }

    /**
     * @return list<DashboardBreakdownItemData>
     */
    private function fulfilmentBreakdown(
        ReportDateRangeData $range,
    ): array {
        $rows =
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
                ->select(
                    'fulfilment_status',
                )
                ->selectRaw(
                    'COUNT(*) AS orders_count',
                )
                ->selectRaw(
                    'COALESCE(SUM(grand_total), 0) AS total',
                )
                ->groupBy(
                    'fulfilment_status',
                )
                ->get();

        $indexed =
            $this->indexRows(
                rows: $rows,

                property: 'fulfilment_status',
            );

        return array_map(
            function (FulfilmentStatus $status) use ($indexed): DashboardBreakdownItemData {
                $row =
                    $indexed->get(
                        $status->value,
                    );

                return new DashboardBreakdownItemData(
                    value: $status->value,

                    label: $status->label(),

                    ordersCount: $this->integer(
                        $row,
                        'orders_count',
                    ),

                    total: $this->integer(
                        $row,
                        'total',
                    ),
                );
            },
            FulfilmentStatus::cases(),
        );
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     * @return Collection<string, stdClass>
     */
    private function indexRows(
        Collection $rows,
        string $property,
    ): Collection {
        return $rows
            ->filter(
                static fn (
                    stdClass $row,
                ): bool => property_exists(
                    $row,
                    $property,
                )
                    && is_string(
                        $row->{$property},
                    ),
            )
            ->keyBy(
                static fn (
                    stdClass $row,
                ): string => (string) $row->{$property},
            );
    }

    private function integer(
        mixed $row,
        string $property,
    ): int {
        if (
            ! $row instanceof stdClass
            || ! property_exists(
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
