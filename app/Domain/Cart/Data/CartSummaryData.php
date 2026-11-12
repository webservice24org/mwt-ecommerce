<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

final readonly class CartSummaryData
{
    /**
     * @param  array<int, CartItemData>  $items
     */
    public function __construct(
        public array $items,
        public int $itemCount,
        public int $subtotal,
    ) {}

    /**
     * @return array{
     *     items: array<int, array{
     *         key: string,
     *         product_id: int,
     *         variant_id: int|null,
     *         quantity: int,
     *         name: string,
     *         slug: string,
     *         variant_name: string|null,
     *         image_url: string|null,
     *         unit_price: int,
     *         line_total: int
     *     }>,
     *     item_count: int,
     *     subtotal: int
     * }
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (
                    CartItemData $item,
                ): array => $item->toArray(),
                $this->items,
            ),

            'item_count' => $this->itemCount,

            'subtotal' => $this->subtotal,
        ];
    }
}
