<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Data\InventoryOperationRowData;
use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Data\InventoryOperationsReportResultData;
use App\Domain\Reporting\Data\InventoryOperationsSummaryData;
use App\Domain\Reporting\Data\ReportPaginationData;
use App\Domain\Reporting\Operations\InventoryAlertClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class GetAdminInventoryOperationsReportQuery
{
    public function __construct(
        private readonly InventoryAlertClassifier $alertClassifier,
    ) {}

    public function get(
        InventoryOperationsReportFiltersData $filters,
        int $page = 1,
    ): InventoryOperationsReportResultData {
        $threshold =
            $this->lowStockThreshold();

        $criticalThreshold =
            $this->criticalStockThreshold();

        $baseQuery =
            $this->baseQuery(
                filters: $filters,
                threshold: $threshold,
            );

        $summary =
            $this->summary(
                query: clone $baseQuery,
                threshold: $threshold,
            );

        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator =
            $baseQuery
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.sku',
                    'products.status',
                    'products.track_inventory',
                    'products.stock_quantity',
                    'products.updated_at',
                ])
                ->orderByRaw(
                    '
                    CASE
                        WHEN products.track_inventory = 1
                            AND products.stock_quantity < 0
                            THEN 0

                        WHEN products.track_inventory = 1
                            AND products.stock_quantity = 0
                            THEN 1

                        WHEN products.track_inventory = 1
                            AND products.stock_quantity <= ?
                            THEN 2

                        WHEN products.track_inventory = 1
                            AND products.stock_quantity <= ?
                            THEN 3

                        WHEN products.track_inventory = 1
                            THEN 4

                        ELSE 5
                    END ASC
                    ',
                    [
                        $criticalThreshold,
                        $threshold,
                    ],
                )
                ->orderBy(
                    'products.stock_quantity',
                    'asc',
                )
                ->orderBy(
                    'products.name',
                    'asc',
                )
                ->orderBy(
                    'products.id',
                    'asc',
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
                ): InventoryOperationRowData => $this->mapRow(
                    row: $row,
                    threshold: $threshold,
                ),

                $paginator
                    ->items(),
            );

        return new InventoryOperationsReportResultData(
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
        InventoryOperationsReportFiltersData $filters,
        int $threshold,
    ): Builder {
        $query =
            DB::table(
                'products',
            );

        $statuses =
            $filters
                ->statusValues();

        if (
            $statuses !== []
        ) {
            $query->whereIn(
                'products.status',
                $statuses,
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
                            'products.name',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'products.sku',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'products.slug',
                            'like',
                            $like,
                        );
                },
            );
        }

        $stockStates =
            $filters
                ->stockStateValues();

        if (
            $stockStates !== []
        ) {
            $query->where(
                static function (Builder $stockQuery) use ($stockStates, $threshold): void {
                    foreach (
                        $stockStates as $index => $stockState
                    ) {
                        $method =
                            $index === 0
                            ? 'where'
                            : 'orWhere';

                        if (
                            $stockState ===
                            'negative_stock'
                        ) {
                            $stockQuery->{$method}(
                                static function (Builder $stateQuery): void {
                                    $stateQuery
                                        ->where(
                                            'products.track_inventory',
                                            true,
                                        )
                                        ->where(
                                            'products.stock_quantity',
                                            '<',
                                            0,
                                        );
                                },
                            );
                        }

                        if (
                            $stockState ===
                            'out_of_stock'
                        ) {
                            $stockQuery->{$method}(
                                static function (Builder $stateQuery): void {
                                    $stateQuery
                                        ->where(
                                            'products.track_inventory',
                                            true,
                                        )
                                        ->where(
                                            'products.stock_quantity',
                                            '=',
                                            0,
                                        );
                                },
                            );
                        }

                        if (
                            $stockState ===
                            'low_stock'
                        ) {
                            $stockQuery->{$method}(
                                static function (Builder $stateQuery) use ($threshold): void {
                                    $stateQuery
                                        ->where(
                                            'products.track_inventory',
                                            true,
                                        )
                                        ->where(
                                            'products.stock_quantity',
                                            '>',
                                            0,
                                        )
                                        ->where(
                                            'products.stock_quantity',
                                            '<=',
                                            $threshold,
                                        );
                                },
                            );
                        }

                        if (
                            $stockState ===
                            'in_stock'
                        ) {
                            $stockQuery->{$method}(
                                static function (Builder $stateQuery) use ($threshold): void {
                                    $stateQuery
                                        ->where(
                                            'products.track_inventory',
                                            true,
                                        )
                                        ->where(
                                            'products.stock_quantity',
                                            '>',
                                            $threshold,
                                        );
                                },
                            );
                        }
                    }
                },
            );
        }

        return $query;
    }

    private function summary(
        Builder $query,
        int $threshold,
    ): InventoryOperationsSummaryData {
        $row =
            (clone $query)
                ->selectRaw(
                    '
                    COUNT(products.id) as products_count,

                    SUM(
                        CASE
                            WHEN products.track_inventory = 1
                            THEN 1
                            ELSE 0
                        END
                    ) as tracked_products_count,

                    SUM(
                        CASE
                            WHEN products.track_inventory = 1
                            AND products.stock_quantity > ?
                            THEN 1
                            ELSE 0
                        END
                    ) as in_stock_count,

                    SUM(
                        CASE
                            WHEN products.track_inventory = 1
                            AND products.stock_quantity > 0
                            AND products.stock_quantity <= ?
                            THEN 1
                            ELSE 0
                        END
                    ) as low_stock_count,

                    SUM(
                        CASE
                            WHEN products.track_inventory = 1
                            AND products.stock_quantity = 0
                            THEN 1
                            ELSE 0
                        END
                    ) as out_of_stock_count,

                    SUM(
                        CASE
                            WHEN products.track_inventory = 1
                            THEN products.stock_quantity
                            ELSE 0
                        END
                    ) as total_stock_units
                    ',
                    [
                        $threshold,
                        $threshold,
                    ],
                )
                ->first();

        $alertRows =
            (clone $query)
                ->select([
                    'products.track_inventory',
                    'products.stock_quantity',
                ])
                ->get();

        $attentionRequiredCount =
            0;

        $criticalAlertCount =
            0;

        $highAlertCount =
            0;

        $negativeStockCount =
            0;

        foreach (
            $alertRows as $alertRow
        ) {
            $alert =
                $this
                    ->alertClassifier
                    ->classify(
                        trackInventory: (bool) $alertRow
                            ->track_inventory,

                        stockQuantity: (int) $alertRow
                            ->stock_quantity,
                    );

            if (
                $alert
                    ->requiresAttention()
            ) {
                $attentionRequiredCount++;
            }

            if (
                $alert
                    ->state
                    ->severity() ===
                'critical'
            ) {
                $criticalAlertCount++;
            }

            if (
                $alert
                    ->state
                    ->severity() ===
                'high'
            ) {
                $highAlertCount++;
            }

            if (
                $alert
                    ->state
                    ->value ===
                'negative_stock'
            ) {
                $negativeStockCount++;
            }
        }

        return new InventoryOperationsSummaryData(
            productsCount: (int) (
                $row
                    ->products_count ??
                0
            ),

            trackedProductsCount: (int) (
                $row
                    ->tracked_products_count ??
                0
            ),

            inStockCount: (int) (
                $row
                    ->in_stock_count ??
                0
            ),

            lowStockCount: (int) (
                $row
                    ->low_stock_count ??
                0
            ),

            outOfStockCount: (int) (
                $row
                    ->out_of_stock_count ??
                0
            ),

            totalStockUnits: (int) (
                $row
                    ->total_stock_units ??
                0
            ),

            lowStockThreshold: $threshold,

            attentionRequiredCount: $attentionRequiredCount,

            criticalAlertCount: $criticalAlertCount,

            highAlertCount: $highAlertCount,

            negativeStockCount: $negativeStockCount,
        );
    }

    private function mapRow(
        object $row,
        int $threshold,
    ): InventoryOperationRowData {
        $trackInventory =
            (bool) $row
                ->track_inventory;

        $stockQuantity =
            (int) $row
                ->stock_quantity;

        $alert =
            $this
                ->alertClassifier
                ->classify(
                    trackInventory: $trackInventory,

                    stockQuantity: $stockQuantity,
                );

        return new InventoryOperationRowData(
            productId: (int) $row->id,

            name: (string) $row->name,

            slug: (string) $row->slug,

            sku: $row->sku !== null
            ? (string) $row->sku
            : null,

            status: (string) $row->status,

            trackInventory: $trackInventory,

            stockQuantity: $stockQuantity,

            lowStockThreshold: $threshold,

            stockState: $this->stockState(
                trackInventory: $trackInventory,

                stockQuantity: $stockQuantity,

                threshold: $threshold,
            ),

            updatedAt: $row->updated_at !== null
            ? CarbonImmutable::parse(
                (string) $row
                    ->updated_at,
            )->toISOString()
            : null,

            alert: $alert,
        );
    }

    private function stockState(
        bool $trackInventory,
        int $stockQuantity,
        int $threshold,
    ): string {
        if (
            ! $trackInventory
        ) {
            return 'not_tracked';
        }

        if (
            $stockQuantity < 0
        ) {
            return 'negative_stock';
        }

        if (
            $stockQuantity === 0
        ) {
            return 'out_of_stock';
        }

        if (
            $stockQuantity <=
            $threshold
        ) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    private function lowStockThreshold(): int
    {
        return max(
            0,
            (int) config(
                'reporting.inventory_operations.low_stock_threshold',
                5,
            ),
        );
    }

    private function criticalStockThreshold(): int
    {
        return min(
            $this->lowStockThreshold(),
            max(
                0,
                (int) config(
                    'reporting.inventory_operations.critical_stock_threshold',
                    2,
                ),
            ),
        );
    }
}
