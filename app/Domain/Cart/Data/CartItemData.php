<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

final readonly class CartItemData
{
    public function __construct(
        public string $key,
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public string $name,
        public string $slug,
        public ?string $variantName,
        public ?string $imageUrl,
        public float $unitPrice,
        public float $lineTotal,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     product_id: int,
     *     variant_id: int|null,
     *     quantity: int,
     *     name: string,
     *     slug: string,
     *     variant_name: string|null,
     *     image_url: string|null,
     *     unit_price: float,
     *     line_total: float
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'quantity' => $this->quantity,
            'name' => $this->name,
            'slug' => $this->slug,
            'variant_name' => $this->variantName,
            'image_url' => $this->imageUrl,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
        ];
    }
}
