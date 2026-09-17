<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use Carbon\CarbonImmutable;

final readonly class CreateProductData
{
    /**
     * @param  list<int>  $categoryIds
     */
    public function __construct(
        public ?int $brandId,
        public ProductType $type,
        public ?string $sku,
        public ?int $price,
        public ?int $compareAtPrice,
        public ?int $costPrice,
        public string $name,
        public ?string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public ProductStatus $status,
        public bool $isFeatured,
        public int $position,
        public ?CarbonImmutable $publishedAt,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public array $categoryIds = [],
    ) {}
}
