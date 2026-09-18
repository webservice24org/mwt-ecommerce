<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontPricingData
{
    public function __construct(
        public ?int $price,
        public ?int $compareAtPrice,
        public bool $onSale,
    ) {}

    /**
     * @return array{
     *     price: int|null,
     *     compare_at_price: int|null,
     *     on_sale: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'on_sale' => $this->onSale,
        ];
    }
}
