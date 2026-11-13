<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\AdminDashboardInventorySummaryData;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GetAdminDashboardInventorySummaryQuery
{
    public function get(
        int $lowStockThreshold = 5,
    ): AdminDashboardInventorySummaryData {
        $lowStockThreshold =
            max(
                0,
                min(
                    $lowStockThreshold,
                    10_000,
                ),
            );

        $row =
            DB::table(
                'products',
            )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 1
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS tracked_products_count
                    SQL,
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 0
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS untracked_products_count
                    SQL,
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 1
                                AND stock_quantity > ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS in_stock_products_count
                    SQL,
                    [
                        $lowStockThreshold,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 1
                                AND stock_quantity > 0
                                AND stock_quantity <= ?
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS low_stock_products_count
                    SQL,
                    [
                        $lowStockThreshold,
                    ],
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 1
                                AND stock_quantity <= 0
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS out_of_stock_products_count
                    SQL,
                )
                ->selectRaw(
                    <<<'SQL'
                    COALESCE(
                        SUM(
                            CASE
                                WHEN track_inventory = 1
                                THEN stock_quantity
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_units_in_stock
                    SQL,
                )
                ->first();

        $row =
            $row instanceof stdClass
            ? $row
            : new stdClass;

        return new AdminDashboardInventorySummaryData(
            trackedProductsCount: $this->integer(
                $row,
                'tracked_products_count',
            ),

            inStockProductsCount: $this->integer(
                $row,
                'in_stock_products_count',
            ),

            lowStockProductsCount: $this->integer(
                $row,
                'low_stock_products_count',
            ),

            outOfStockProductsCount: $this->integer(
                $row,
                'out_of_stock_products_count',
            ),

            untrackedProductsCount: $this->integer(
                $row,
                'untracked_products_count',
            ),

            totalUnitsInStock: $this->integer(
                $row,
                'total_units_in_stock',
            ),

            lowStockThreshold: $lowStockThreshold,
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
