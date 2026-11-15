<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ProductAnalyticsSummaryData
{
    public function __construct(
        public int $productGroupsCount,
        public int $quantitySold,
        public int $ordersCount,
        public int $grossSales,
        public int $averageRevenuePerOrder,
        public int $averageRevenuePerItem,
        public int $currentProductRevenue,
        public int $historicalProductRevenue,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'product_groups_count' => $this->productGroupsCount,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,

            'average_revenue_per_order' => $this->averageRevenuePerOrder,

            'average_revenue_per_item' => $this->averageRevenuePerItem,

            'current_product_revenue' => $this->currentProductRevenue,

            'historical_product_revenue' => $this->historicalProductRevenue,
        ];
    }
}
