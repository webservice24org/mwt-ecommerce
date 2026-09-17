<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

final readonly class CreateProductVariantData
{
    /**
     * @param  list<int>  $attributeValueIds
     */
    public function __construct(
        public string $sku,
        public ?string $name,
        public ?int $price,
        public ?int $compareAtPrice,
        public ?int $costPrice,
        public ?string $barcode,
        public int $position,
        public bool $isActive,
        public bool $isDefault,
        public ?string $weight,
        public array $attributeValueIds = [],
    ) {}
}
