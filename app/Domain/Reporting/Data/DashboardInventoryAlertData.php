<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class DashboardInventoryAlertData
{
    public function __construct(
        public int $productId,

        public string $name,

        public string $slug,

        public ?string $sku,

        public string $status,

        public int $stockQuantity,

        public int $lowStockThreshold,

        public bool $outOfStock,
    ) {}

    /**
     * @return array{
     *     product_id: int,
     *     name: string,
     *     slug: string,
     *     sku: string|null,
     *     status: string,
     *     stock_quantity: int,
     *     low_stock_threshold: int,
     *     out_of_stock: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'name' => $this->name,

            'slug' => $this->slug,

            'sku' => $this->sku,

            'status' => $this->status,

            'stock_quantity' => $this->stockQuantity,

            'low_stock_threshold' => $this->lowStockThreshold,

            'out_of_stock' => $this->outOfStock,
        ];
    }
}
