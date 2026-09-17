<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class ResolvedProductPricing
{
    public function __construct(
        public ?int $price,
        public ?int $compareAtPrice,
        public ?int $costPrice,
        public bool $priceInherited,
        public bool $compareAtPriceInherited,
        public bool $costPriceInherited,
    ) {}

    public function hasPrice(): bool
    {
        return $this->price !== null;
    }

    public function isOnSale(): bool
    {
        return $this->price !== null
            && $this->compareAtPrice !== null
            && $this->compareAtPrice > $this->price;
    }
}
