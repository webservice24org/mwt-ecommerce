<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontCategoryData;
use App\Domain\Catalog\Data\Storefront\StorefrontHomeData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Category;
use App\Models\Product;

final readonly class StorefrontHomeQuery
{
    private const FEATURED_LIMIT = 8;

    private const NEW_ARRIVALS_LIMIT = 8;

    private const CATEGORY_LIMIT = 6;

    public function __construct(
        private StorefrontProductDataFactory $productDataFactory,
    ) {}

    public function get(): StorefrontHomeData
    {
        $featuredProducts = Product::query()
            ->published()
            ->where('is_featured', true)
            ->with($this->productRelations())
            ->latest('published_at')
            ->latest('id')
            ->limit(self::FEATURED_LIMIT)
            ->get()
            ->map(
                fn (Product $product) => $this
                    ->productDataFactory
                    ->card($product),
            )
            ->values()
            ->all();

        $newArrivals = Product::query()
            ->published()
            ->with($this->productRelations())
            ->latest('published_at')
            ->latest('id')
            ->limit(self::NEW_ARRIVALS_LIMIT)
            ->get()
            ->map(
                fn (Product $product) => $this
                    ->productDataFactory
                    ->card($product),
            )
            ->values()
            ->all();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->limit(self::CATEGORY_LIMIT)
            ->get()
            ->map(
                static fn (
                    Category $category,
                ) => new StorefrontCategoryData(
                    id: $category->id,
                    name: $category->name,
                    slug: $category->slug,
                ),
            )
            ->values()
            ->all();

        return new StorefrontHomeData(
            featuredProducts: $featuredProducts,
            newArrivals: $newArrivals,
            categories: $categories,
        );
    }

    /**
     * @return array<string|int, string|\Closure>
     */
    private function productRelations(): array
    {
        return [
            'brand',

            'images' => static fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('position')
                ->orderBy('id'),

            'variants' => static fn ($query) => $query
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('position')
                ->orderBy('id'),
        ];
    }
}
