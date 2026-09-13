<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\ProductStatus;
use Carbon\CarbonImmutable;

final readonly class CreateProductData
{
    /**
     * @param  list<int>  $categoryIds
     */
    public function __construct(
        public ?int $brandId,
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
