<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class DashboardTrendPointData
{
    public function __construct(
        public string $date,

        public int $ordersCount,

        public int $netSales,

        public int $productsSold,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     orders_count: int,
     *     net_sales: int,
     *     products_sold: int
     * }
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,

            'orders_count' => $this->ordersCount,

            'net_sales' => $this->netSales,

            'products_sold' => $this->productsSold,
        ];
    }
}
