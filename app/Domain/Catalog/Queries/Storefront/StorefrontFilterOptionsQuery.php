<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontAttributeFilterData;
use App\Domain\Catalog\Data\Storefront\StorefrontFilterOptionData;
use App\Domain\Catalog\Data\Storefront\StorefrontFilterOptionsData;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheKey;
use App\Support\Cache\StorefrontCatalogCacheTtl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class StorefrontFilterOptionsQuery
{
    public function __construct(
        private StorefrontCatalogCache $cache,
        private StorefrontCatalogCacheTtl $cacheTtl,
    ) {}

    public function get(
        ?Category $category = null,
        bool $includeCategories = true,
    ): StorefrontFilterOptionsData {
        return $this->cache->remember(
            StorefrontCatalogCacheKey::filterOptions(
                categorySlug: $category?->slug,
                includeCategories: $includeCategories,
            ),
            fn (): StorefrontFilterOptionsData => $this->build(
                category: $category,
                includeCategories: $includeCategories,
            ),
            $this->cacheTtl->seconds(),
        );
    }

    private function build(
        ?Category $category,
        bool $includeCategories,
    ): StorefrontFilterOptionsData {
        return new StorefrontFilterOptionsData(
            brands: $this->brands($category),
            categories: $includeCategories
                ? $this->categories()
                : [],
            attributes: $this->attributes($category),
            minPrice: $this->minimumPrice($category),
            maxPrice: $this->maximumPrice($category),
        );
    }

    /**
     * @return list<StorefrontFilterOptionData>
     */
    private function brands(
        ?Category $category,
    ): array {
        return Brand::query()
            ->select([
                'brands.id',
                'brands.name',
                'brands.slug',
            ])
            ->where(
                'brands.is_active',
                true,
            )
            ->whereHas(
                'products',
                function (Builder $query) use ($category): void {
                    $this->applyPublicProductScope(
                        $query,
                        $category,
                    );
                },
            )
            ->orderBy(
                'brands.name',
            )
            ->get()
            ->map(
                static fn (
                    Brand $brand,
                ): StorefrontFilterOptionData => new StorefrontFilterOptionData(
                    id: $brand->id,
                    name: $brand->name,
                    slug: $brand->slug,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @return list<StorefrontFilterOptionData>
     */
    private function categories(): array
    {
        return Category::query()
            ->select([
                'categories.id',
                'categories.name',
                'categories.slug',
            ])
            ->where(
                'categories.is_active',
                true,
            )
            ->whereHas(
                'products',
                static fn (
                    Builder $query,
                ) => $query->published(),
            )
            ->orderBy(
                'categories.position',
            )
            ->orderBy(
                'categories.name',
            )
            ->get()
            ->map(
                static fn (
                    Category $category,
                ): StorefrontFilterOptionData => new StorefrontFilterOptionData(
                    id: $category->id,
                    name: $category->name,
                    slug: $category->slug,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @return list<StorefrontAttributeFilterData>
     */
    private function attributes(
        ?Category $category,
    ): array {
        $attributes = ProductAttribute::query()
            ->select([
                'attributes.id',
                'attributes.name',
                'attributes.slug',
            ])
            ->where(
                'attributes.is_active',
                true,
            )
            ->whereHas(
                'values',
                function (Builder $valueQuery) use ($category): void {
                    $this->applyPublicAttributeValueScope(
                        $valueQuery,
                        $category,
                    );
                },
            )
            ->with([
                'values' => function (Relation $relation) use ($category): void {
                    /*
                    * ProductAttribute::values() is a HasMany relation.
                    * Laravel's with() callback is statically typed as the
                    * broader Relation contract, so we intentionally work
                    * through that contract here.
                    */

                    /** @var Builder<AttributeValue> $valueQuery */
                    $valueQuery = $relation->getQuery();

                    $valueQuery
                        ->select([
                            'attribute_values.id',
                            'attribute_values.attribute_id',
                            'attribute_values.name',
                            'attribute_values.slug',
                        ]);

                    $this->applyPublicAttributeValueScope(
                        $valueQuery,
                        $category,
                    );

                    $valueQuery
                        ->orderBy(
                            'attribute_values.position',
                        )
                        ->orderBy(
                            'attribute_values.name',
                        );
                },
            ])
            ->orderBy(
                'attributes.position',
            )
            ->orderBy(
                'attributes.name',
            )
            ->get();

        return $attributes
            ->map(
                static function (
                    ProductAttribute $attribute,
                ): StorefrontAttributeFilterData {
                    return new StorefrontAttributeFilterData(
                        id: $attribute->id,
                        name: $attribute->name,
                        slug: $attribute->slug,
                        values: $attribute->values
                            ->map(
                                static fn (
                                    AttributeValue $value,
                                ): StorefrontFilterOptionData => new StorefrontFilterOptionData(
                                    id: $value->id,
                                    name: $value->name,
                                    slug: $value->slug,
                                ),
                            )
                            ->values()
                            ->all(),
                    );
                },
            )
            ->filter(
                static fn (
                    StorefrontAttributeFilterData $attribute,
                ): bool => $attribute->values !== [],
            )
            ->values()
            ->all();
    }

    /**
     * @param  Builder<AttributeValue>  $query
     */
    private function applyPublicAttributeValueScope(
        Builder $query,
        ?Category $category,
    ): void {
        $query
            ->where(
                'attribute_values.is_active',
                true,
            )
            ->whereHas(
                'variants',
                function (Builder $variantQuery) use ($category): void {
                    $variantQuery
                        ->where(
                            'product_variants.is_active',
                            true,
                        )
                        ->whereHas(
                            'product',
                            function (Builder $productQuery) use ($category): void {
                                $this->applyPublicProductScope(
                                    $productQuery,
                                    $category,
                                );
                            },
                        );
                },
            );
    }

    private function minimumPrice(
        ?Category $category,
    ): ?int {
        return $this->priceBoundary(
            category: $category,
            minimum: true,
        );
    }

    private function maximumPrice(
        ?Category $category,
    ): ?int {
        return $this->priceBoundary(
            category: $category,
            minimum: false,
        );
    }

    private function priceBoundary(
        ?Category $category,
        bool $minimum,
    ): ?int {
        $aggregate = $minimum
            ? 'MIN'
            : 'MAX';

        $query = Product::query()
            ->published();

        $this->applyCategoryScope(
            $query,
            $category,
        );

        $value = $query->selectRaw(
            sprintf(
                <<<'SQL'
%s(
    CASE
        WHEN products.type = 'simple'
            THEN products.price
        ELSE COALESCE(
            (
                SELECT %s(
                    COALESCE(
                        product_variants.price,
                        products.price
                    )
                )
                FROM product_variants
                WHERE product_variants.product_id = products.id
                  AND product_variants.is_active = 1
            ),
            products.price
        )
    END
) AS price_boundary
SQL,
                $aggregate,
                $aggregate,
            ),
        )->value(
            'price_boundary',
        );

        return $value !== null
            ? (int) $value
            : null;
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPublicProductScope(
        Builder $query,
        ?Category $category,
    ): void {
        $query->published();

        $this->applyCategoryScope(
            $query,
            $category,
        );
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyCategoryScope(
        Builder $query,
        ?Category $category,
    ): void {
        if ($category === null) {
            return;
        }

        $query->whereHas(
            'categories',
            static fn (
                Builder $categoryQuery,
            ) => $categoryQuery->where(
                'categories.id',
                $category->id,
            ),
        );
    }
}
