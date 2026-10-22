<?php

declare(strict_types=1);

namespace App\Domain\Orders\Data;

final readonly class OrderItemSnapshotData
{
    public function __construct(
        public int $productId,
        public ?int $variantId,
        public string $productName,
        public ?string $sku,
        public ?string $variantDescription,
        public int $unitPrice,
        public int $quantity,
        public bool $stockWasDecremented,
        public int $lineTotal,
    ) {}

    /**
     * @return array{
     *     product_id: int,
     *     variant_id: int|null,
     *     product_name: string,
     *     sku: string|null,
     *     variant_description: string|null,
     *     unit_price: int,
     *     quantity: int,
     *     stock_was_decremented: bool,
     *     line_total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,

            'variant_id' => $this->variantId,

            'product_name' => $this->productName,

            'sku' => $this->sku,

            'variant_description' => $this->variantDescription,

            'unit_price' => $this->unitPrice,

            'quantity' => $this->quantity,

            'stock_was_decremented' => $this->stockWasDecremented,

            'line_total' => $this->lineTotal,
        ];
    }
}
