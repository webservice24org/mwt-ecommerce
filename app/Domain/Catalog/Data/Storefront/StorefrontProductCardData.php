<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Domain\Catalog\Enums\ProductType;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class StorefrontProductCardData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ProductType $type,
        public ?string $shortDescription,
        public bool $isFeatured,
        public StorefrontProductListingPricingData $pricing,
        public ?StorefrontImageData $image,
        public ?StorefrontBrandData $brand,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     type: string,
     *     short_description: string|null,
     *     is_featured: bool,
     *     pricing: array{
     *         min_price: int|null,
     *         max_price: int|null,
     *         compare_at_price: int|null,
     *         on_sale: bool,
     *         varies: bool
     *     },
     *     image: array{
     *         id: int,
     *         url: string,
     *         alt: string|null,
     *         width: int|null,
     *         height: int|null
     *     }|null,
     *     brand: array{
     *         id: int,
     *         name: string,
     *         slug: string
     *     }|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'short_description' => $this->shortDescription,
            'is_featured' => $this->isFeatured,
            'pricing' => $this->pricing->toArray(),
            'image' => $this->image?->toArray(),
            'brand' => $this->brand?->toArray(),
        ];
    }
}
