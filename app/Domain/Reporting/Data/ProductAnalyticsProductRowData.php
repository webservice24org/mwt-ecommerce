<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ProductAnalyticsProductRowData
{
    public function __construct(
        public ?int $productId,
        public string $productName,
        public ?string $sku,
        public bool $historical,
        public int $quantitySold,
        public int $ordersCount,
        public int $grossSales,
        public int $averageRevenuePerItem,
    ) {}

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'product_name' => $this->productName,

            'sku' => $this->sku,

            'historical' => $this->historical,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,

            'average_revenue_per_item' => $this->averageRevenuePerItem,
        ];
    }
}
