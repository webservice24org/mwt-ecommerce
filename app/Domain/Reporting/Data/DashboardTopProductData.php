<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class DashboardTopProductData
{
    public function __construct(
        public ?int $productId,

        public string $productName,

        public ?string $sku,

        public int $quantitySold,

        public int $ordersCount,

        public int $grossSales,
    ) {}

    /**
     * @return array{
     *     product_id: int|null,
     *     product_name: string,
     *     sku: string|null,
     *     quantity_sold: int,
     *     orders_count: int,
     *     gross_sales: int
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'product_name' => $this->productName,

            'sku' => $this->sku,

            'quantity_sold' => $this->quantitySold,

            'orders_count' => $this->ordersCount,

            'gross_sales' => $this->grossSales,
        ];
    }
}
