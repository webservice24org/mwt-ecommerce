<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Domain\Catalog\Enums\ProductType;

final readonly class StorefrontProductDetailData
{
    /**
     * @param  list<StorefrontCategoryData>  $categories
     * @param  list<StorefrontImageData>  $images
     * @param  list<StorefrontVariantData>  $variants
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ProductType $type,
        public ?string $sku,
        public ?string $shortDescription,
        public ?string $description,
        public StorefrontPricingData $pricing,
        public ?StorefrontBrandData $brand,
        public array $categories,
        public array $images,
        public array $variants,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?StorefrontVideoData $video,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'sku' => $this->sku,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'pricing' => $this->pricing->toArray(),
            'brand' => $this->brand?->toArray(),

            'categories' => array_map(
                static fn (
                    StorefrontCategoryData $category,
                ): array => $category->toArray(),
                $this->categories,
            ),

            'images' => array_map(
                static fn (
                    StorefrontImageData $image,
                ): array => $image->toArray(),
                $this->images,
            ),

            'variants' => array_map(
                static fn (
                    StorefrontVariantData $variant,
                ): array => $variant->toArray(),
                $this->variants,
            ),

            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'video' => $this->video?->toArray(),
        ];
    }
}
