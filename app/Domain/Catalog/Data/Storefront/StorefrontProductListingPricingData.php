<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

final readonly class StorefrontProductListingPricingData
{
    public function __construct(
        public ?int $minPrice,
        public ?int $maxPrice,
        public ?int $compareAtPrice,
        public bool $onSale,
    ) {}

    /**
     * @return array{
     *     min_price: int|null,
     *     max_price: int|null,
     *     compare_at_price: int|null,
     *     on_sale: bool,
     *     varies: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'compare_at_price' => $this->compareAtPrice,
            'on_sale' => $this->onSale,
            'varies' => $this->minPrice !== null
                && $this->maxPrice !== null
                && $this->minPrice !== $this->maxPrice,
        ];
    }
}
