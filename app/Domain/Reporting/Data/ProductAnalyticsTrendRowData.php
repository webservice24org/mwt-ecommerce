<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class ProductAnalyticsTrendRowData
{
    public function __construct(
        public string $date,
        public string $label,
        public int $quantitySold,
        public int $ordersCount,
        public int $grossSales,
        public int $currentProductRevenue,
        public int $historicalProductRevenue,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,

            'label' => $this->label,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,

            'current_product_revenue' => $this->currentProductRevenue,

            'historical_product_revenue' => $this->historicalProductRevenue,
        ];
    }
}
