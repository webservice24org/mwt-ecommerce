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
        public float $subtotal,
    ) {}

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     item_count: int,
     *     subtotal: float
     * }
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (CartItemData $item): array => $item->toArray(),
                $this->items,
            ),
            'item_count' => $this->itemCount,
            'subtotal' => $this->subtotal,
        ];
    }
}
