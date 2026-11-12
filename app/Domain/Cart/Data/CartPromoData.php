<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

final readonly class CartPromoData
{
    public function __construct(
        public int $promoCodeId,
        public string $code,
        public int $discountAmount,
    ) {}

    /**
     * @return array{
     *     promo_code_id: int,
     *     code: string,
     *     discount_amount: int
     * }
     */
    public function toArray(): array
    {
        return [
            'promo_code_id' => $this->promoCodeId,

            'code' => $this->code,

            'discount_amount' => $this->discountAmount,
        ];
    }
}
