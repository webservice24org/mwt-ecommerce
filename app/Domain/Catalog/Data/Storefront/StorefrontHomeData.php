<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class StorefrontHomeData implements Arrayable
{
    /**
     * @param  list<StorefrontProductCardData>  $featuredProducts
     * @param  list<StorefrontProductCardData>  $newArrivals
     * @param  list<StorefrontCategoryData>  $categories
     */
    public function __construct(
        public array $featuredProducts,
        public array $newArrivals,
        public array $categories,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'featured_products' => array_map(
                static fn (
                    StorefrontProductCardData $product,
                ): array => $product->toArray(),
                $this->featuredProducts,
            ),

            'new_arrivals' => array_map(
                static fn (
                    StorefrontProductCardData $product,
                ): array => $product->toArray(),
                $this->newArrivals,
            ),

            'categories' => array_map(
                static fn (
                    StorefrontCategoryData $category,
                ): array => $category->toArray(),
                $this->categories,
            ),
        ];
    }
}
