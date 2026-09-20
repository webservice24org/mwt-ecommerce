<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Enums\StorefrontProductSort;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;

final readonly class StorefrontProductListingQuery
{
    /**
     * @return Builder<Product>
     */
    public function build(
        StorefrontProductFiltersData $filters,
        ?Category $category = null,
    ): Builder {
        $query = Product::query()
            ->published()
            ->with([
                'brand:id,name,slug',

                'primaryImage',

                'variants' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->select([
                        'id',
                        'product_id',
                        'price',
                        'compare_at_price',
                        'cost_price',
                        'is_active',
                    ]),
            ]);

        $this->applyCategory(
            $query,
            $category,
            $filters->category,
        );

        $this->applyBrand(
            $query,
            $filters->brand,
        );

        $this->applyPriceRange(
            $query,
            $filters,
        );

        $this->applyAttributes(
            $query,
            $filters->attributes,
        );

        $this->applySorting(
            $query,
            $filters->sort,
        );

        return $query;
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyCategory(
        Builder $query,
        ?Category $category,
        ?string $categorySlug,
    ): void {
        if ($category !== null) {
            $query->whereHas(
                'categories',
                static fn (Builder $categoryQuery) => $categoryQuery
                    ->where(
                        'categories.id',
                        $category->id,
                    ),
            );

            return;
        }

        if ($categorySlug === null) {
            return;
        }

        $query->whereHas(
            'categories',
            static fn (Builder $categoryQuery) => $categoryQuery
                ->where(
                    'categories.slug',
                    $categorySlug,
                )
                ->where(
                    'categories.is_active',
                    true,
                ),
        );
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyBrand(
        Builder $query,
        ?string $brandSlug,
    ): void {
        if ($brandSlug === null) {
            return;
        }

        $query->whereHas(
            'brand',
            static fn (Builder $brandQuery) => $brandQuery
                ->where('brands.slug', $brandSlug)
                ->where('brands.is_active', true),
        );
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPriceRange(
        Builder $query,
        StorefrontProductFiltersData $filters,
    ): void {
        if (
            $filters->minPrice === null
            && $filters->maxPrice === null
        ) {
            return;
        }

        /*
         * Phase 3.7 price filtering is based on an effective purchasable
         * price:
         *
         * Simple:
         *      products.price
         *
         * Variable:
         *      COALESCE(product_variants.price, products.price)
         *
         * Only active variants participate.
         */
        $query->where(
            function (Builder $priceQuery) use ($filters): void {
                $priceQuery
                    ->where(
                        function (Builder $simpleQuery) use ($filters): void {
                            $simpleQuery->where(
                                'products.type',
                                'simple',
                            );

                            $this->applySimplePriceBounds(
                                $simpleQuery,
                                $filters,
                            );
                        },
                    )
                    ->orWhere(
                        function (Builder $variableQuery) use ($filters): void {
                            $variableQuery
                                ->where(
                                    'products.type',
                                    'variable',
                                )
                                ->whereHas(
                                    'variants',
                                    function (Builder $variantQuery) use ($filters): void {
                                        $variantQuery->where(
                                            'product_variants.is_active',
                                            true,
                                        );

                                        $this->applyVariantPriceBounds(
                                            $variantQuery,
                                            $filters,
                                        );
                                    },
                                );
                        },
                    );
            },
        );
    }

    /**
     * Require one active variant to contain every selected
     * attribute/value pair.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, string>  $attributes
     */
    private function applyAttributes(
        Builder $query,
        array $attributes,
    ): void {
        if ($attributes === []) {
            return;
        }

        $query->whereHas(
            'variants',
            function (Builder $variantQuery) use ($attributes): void {
                $variantQuery->where(
                    'product_variants.is_active',
                    true,
                );

                foreach ($attributes as $attributeSlug => $valueSlug) {
                    $variantQuery->whereHas(
                        'attributeValues',
                        static function (Builder $valueQuery) use (
                            $attributeSlug,
                            $valueSlug,
                        ): void {
                            $valueQuery
                                ->where(
                                    'attribute_values.slug',
                                    $valueSlug,
                                )
                                ->where(
                                    'attribute_values.is_active',
                                    true,
                                )
                                ->whereHas(
                                    'attribute',
                                    static fn (Builder $attributeQuery) => $attributeQuery
                                        ->where(
                                            'attributes.slug',
                                            $attributeSlug,
                                        )
                                        ->where(
                                            'attributes.is_active',
                                            true,
                                        ),
                                );
                        },
                    );
                }
            },
        );
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySimplePriceBounds(
        Builder $query,
        StorefrontProductFiltersData $filters,
    ): void {
        if ($filters->minPrice !== null) {
            $query->where(
                'products.price',
                '>=',
                $filters->minPrice,
            );
        }

        if ($filters->maxPrice !== null) {
            $query->where(
                'products.price',
                '<=',
                $filters->maxPrice,
            );
        }
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    private function applyVariantPriceBounds(
        Builder $query,
        StorefrontProductFiltersData $filters,
    ): void {
        if ($filters->minPrice !== null) {
            $query->whereRaw(
                'COALESCE(product_variants.price, products.price) >= ?',
                [$filters->minPrice],
            );
        }

        if ($filters->maxPrice !== null) {
            $query->whereRaw(
                'COALESCE(product_variants.price, products.price) <= ?',
                [$filters->maxPrice],
            );
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySorting(
        Builder $query,
        StorefrontProductSort $sort,
    ): void {
        match ($sort) {
            StorefrontProductSort::Newest => $query
                ->orderByDesc('products.published_at')
                ->orderByDesc('products.id'),

            StorefrontProductSort::NameAscending => $query
                ->orderBy('products.name')
                ->orderBy('products.id'),

            StorefrontProductSort::NameDescending => $query
                ->orderByDesc('products.name')
                ->orderByDesc('products.id'),

            StorefrontProductSort::PriceAscending => $this
                ->applyPriceAscendingSort($query),

            StorefrontProductSort::PriceDescending => $this
                ->applyPriceDescendingSort($query),
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPriceAscendingSort(
        Builder $query,
    ): void {
        $query
            ->orderByRaw(
                $this->effectiveMinimumPriceSql().' IS NULL',
            )
            ->orderByRaw(
                $this->effectiveMinimumPriceSql().' ASC',
            )
            ->orderBy('products.id');
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPriceDescendingSort(
        Builder $query,
    ): void {
        $query
            ->orderByRaw(
                $this->effectiveMaximumPriceSql().' IS NULL',
            )
            ->orderByRaw(
                $this->effectiveMaximumPriceSql().' DESC',
            )
            ->orderByDesc('products.id');
    }

    private function effectiveMinimumPriceSql(): string
    {
        return <<<'SQL'
CASE
    WHEN products.type = 'simple'
        THEN products.price
    ELSE COALESCE(
        (
            SELECT MIN(
                COALESCE(product_variants.price, products.price)
            )
            FROM product_variants
            WHERE product_variants.product_id = products.id
              AND product_variants.is_active = 1
        ),
        products.price
    )
END
SQL;
    }

    private function effectiveMaximumPriceSql(): string
    {
        return <<<'SQL'
CASE
    WHEN products.type = 'simple'
        THEN products.price
    ELSE COALESCE(
        (
            SELECT MAX(
                COALESCE(product_variants.price, products.price)
            )
            FROM product_variants
            WHERE product_variants.product_id = products.id
              AND product_variants.is_active = 1
        ),
        products.price
    )
END
SQL;
    }
}
