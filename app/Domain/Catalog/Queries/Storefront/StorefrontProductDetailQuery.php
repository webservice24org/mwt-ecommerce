<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheKey;

final class StorefrontProductDetailQuery
{
    public function __construct(
        private readonly StorefrontProductDataFactory $productData,
        private readonly StorefrontCatalogCache $cache,
    ) {}

    public function findBySlug(string $slug): ?StorefrontProductDetailData
    {
        return $this->cache->rememberNotNull(
            StorefrontCatalogCacheKey::product($slug),
            fn (): ?StorefrontProductDetailData => $this->findPublishedBySlug($slug),
        );
    }

    private function findPublishedBySlug(
        string $slug,
    ): ?StorefrontProductDetailData {
        $product = Product::query()
            ->published()
            ->with([
                'brand:id,name,slug',

                'categories' => static fn ($query) => $query
                    ->select([
                        'categories.id',
                        'categories.name',
                        'categories.slug',
                    ])
                    ->where('categories.is_active', true)
                    ->orderBy('categories.position')
                    ->orderBy('categories.name'),

                'images' => static fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position')
                    ->orderBy('id'),

                'variants' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('id'),

                'variants.attributeValues' => static fn ($query) => $query
                    ->where(
                        'attribute_values.is_active',
                        true,
                    )
                    ->whereHas(
                        'attribute',
                        static fn ($attributeQuery) => $attributeQuery
                            ->where(
                                'attributes.is_active',
                                true,
                            ),
                    ),

                'variants.attributeValues.attribute',
            ])
            ->where('slug', $slug)
            ->first();

        if ($product === null) {
            return null;
        }

        return $this->productData->detail($product);
    }
}
