<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

final readonly class CartPricingSummaryData
{
    public function __construct(
        public CartSummaryData $cart,
        public ?CartPromoData $promo,
        public int $discountTotal,
        public int $grandTotal,
    ) {}

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     item_count: int,
     *     subtotal: int,
     *     promo: array{
     *         promo_code_id: int,
     *         code: string,
     *         discount_amount: int
     *     }|null,
     *     discount_total: int,
     *     grand_total: int
     * }
     */
    public function toArray(): array
    {
        return [
            ...$this->cart->toArray(),

            'promo' => $this->promo?->toArray(),

            'discount_total' => $this->discountTotal,

            'grand_total' => $this->grandTotal,
        ];
    }
}
