<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class InventoryOperationRowData
{
    public function __construct(
        public int $productId,
        public string $name,
        public string $slug,
        public ?string $sku,
        public string $status,

        public bool $trackInventory,
        public int $stockQuantity,

        public int $lowStockThreshold,
        public string $stockState,

        public ?string $updatedAt,
        public InventoryAlertData $alert,
    ) {}

    /**
     * @return array<string, array<string, bool|int|string>|bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'name' => $this->name,

            'slug' => $this->slug,

            'sku' => $this->sku,

            'status' => $this->status,

            'track_inventory' => $this->trackInventory,

            'stock_quantity' => $this->stockQuantity,

            'low_stock_threshold' => $this->lowStockThreshold,

            'stock_state' => $this->stockState,

            'updated_at' => $this->updatedAt,
            'alert' => $this->alert->toArray(),
        ];
    }
}
