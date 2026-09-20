<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontCategoryData;
use App\Domain\Catalog\Data\Storefront\StorefrontHomeData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheKey;
use App\Support\Cache\StorefrontCatalogCacheTtl;

final readonly class StorefrontHomeQuery
{
    private const FEATURED_LIMIT = 8;

    private const NEW_ARRIVALS_LIMIT = 8;

    private const CATEGORY_LIMIT = 6;

    public function __construct(
        private StorefrontProductDataFactory $productDataFactory,
        private StorefrontCatalogCache $cache,
        private StorefrontCatalogCacheTtl $cacheTtl,
    ) {}

    public function get(): StorefrontHomeData
    {
        return $this->cache->remember(
            StorefrontCatalogCacheKey::home(),
            fn (): StorefrontHomeData => $this->build(),
            $this->cacheTtl->seconds(),
        );
    }

    private function build(): StorefrontHomeData
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
            'primaryImage',

            'variants' => static fn ($query) => $query
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('position')
                ->orderBy('id'),
        ];
    }
}
