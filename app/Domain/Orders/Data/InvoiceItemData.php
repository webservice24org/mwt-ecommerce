<?php

declare(strict_types=1);

namespace App\Domain\Orders\Data;

final readonly class InvoiceItemData
{
    public function __construct(
        public string $productName,
        public ?string $sku,
        public ?string $variantDescription,
        public int $unitPrice,
        public int $quantity,
        public int $lineTotal,
    ) {}

    /**
     * @return array{
     *     product_name: string,
     *     sku: string|null,
     *     variant_description: string|null,
     *     unit_price: int,
     *     quantity: int,
     *     line_total: int
     * }
     */
    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,

            'sku' => $this->sku,

            'variant_description' => $this->variantDescription,

            'unit_price' => $this->unitPrice,

            'quantity' => $this->quantity,

            'line_total' => $this->lineTotal,
        ];
    }
}
