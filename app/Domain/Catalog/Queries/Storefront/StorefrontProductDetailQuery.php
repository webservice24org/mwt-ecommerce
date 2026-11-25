<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheKey;
use Illuminate\Database\Eloquent\Builder;

final class StorefrontProductDetailQuery
{
    public function __construct(
        private readonly StorefrontProductDataFactory $productData,
        private readonly StorefrontCatalogCache $cache,
    ) {}

    /**
     * Existing cached storefront DTO lookup.
     *
     * Keep this method for existing consumers that only
     * need StorefrontProductDetailData.
     */
    public function findBySlug(
        string $slug,
    ): ?StorefrontProductDetailData {
        return $this
            ->cache
            ->rememberNotNull(
                StorefrontCatalogCacheKey::product(
                    $slug,
                ),
                fn (): ?StorefrontProductDetailData => $this->findPublishedDataBySlug(
                    $slug,
                ),
            );
    }

    /**
     * Authoritative model lookup.
     *
     * Used by Product storefront pages that need both:
     *
     * - storefront product DTO generation
     * - authoritative SEO resolution
     *
     * Returning the Product model prevents a second Product
     * query solely for SEO metadata.
     */
    public function findBySlugOrFail(
        string $slug,
    ): Product {
        return $this
            ->query()
            ->where(
                'slug',
                $slug,
            )
            ->firstOrFail();
    }

    /**
     * Cached DTO path used by findBySlug().
     */
    private function findPublishedDataBySlug(
        string $slug,
    ): ?StorefrontProductDetailData {
        $product =
            $this
                ->query()
                ->where(
                    'slug',
                    $slug,
                )
                ->first();

        if (
            $product === null
        ) {
            return null;
        }

        return $this
            ->productData
            ->detail(
                $product,
            );
    }

    /**
     * Single authoritative Product detail query.
     *
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
            ->published()
            ->with([
                /*
                 * ==================================================
                 * SEO
                 * ==================================================
                 */
                'seoMeta',

                /*
                 * Used by SeoEntitySourceFactory as the preferred
                 * entity-level social-image fallback.
                 */
                'featuredImage',

                /*
                 * ==================================================
                 * PRODUCT DETAIL
                 * ==================================================
                 */
                'brand:id,name,slug',

                'categories' => static fn ($query) => $query
                    ->select([
                        'categories.id',
                        'categories.name',
                        'categories.slug',
                    ])
                    ->where(
                        'categories.is_active',
                        true,
                    )
                    ->orderBy(
                        'categories.position',
                    )
                    ->orderBy(
                        'categories.name',
                    ),

                'images' => static fn ($query) => $query
                    ->reorder()
                    ->orderByDesc(
                        'is_primary',
                    )
                    ->orderBy(
                        'position',
                    )
                    ->orderBy(
                        'id',
                    ),

                'variants' => static fn ($query) => $query
                    ->where(
                        'is_active',
                        true,
                    )
                    ->orderByDesc(
                        'is_default',
                    )
                    ->orderBy(
                        'position',
                    )
                    ->orderBy(
                        'id',
                    ),

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

                /*
                 * StorefrontProductDataFactory may already use
                 * this relation for product media.
                 */
                'video',
            ]);
    }
}
