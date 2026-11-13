<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class AdminDashboardInventorySummaryData
{
    public function __construct(
        public int $trackedProductsCount,

        public int $inStockProductsCount,

        public int $lowStockProductsCount,

        public int $outOfStockProductsCount,

        public int $untrackedProductsCount,

        public int $totalUnitsInStock,

        public int $lowStockThreshold,
    ) {}

    /**
     * @return array{
     *     tracked_products_count: int,
     *     in_stock_products_count: int,
     *     low_stock_products_count: int,
     *     out_of_stock_products_count: int,
     *     untracked_products_count: int,
     *     total_units_in_stock: int,
     *     low_stock_threshold: int
     * }
     */
    public function toArray(): array
    {
        return [
            'tracked_products_count' => $this->trackedProductsCount,

            'in_stock_products_count' => $this->inStockProductsCount,

            'low_stock_products_count' => $this->lowStockProductsCount,

            'out_of_stock_products_count' => $this->outOfStockProductsCount,

            'untracked_products_count' => $this->untrackedProductsCount,

            'total_units_in_stock' => $this->totalUnitsInStock,

            'low_stock_threshold' => $this->lowStockThreshold,
        ];
    }
}
