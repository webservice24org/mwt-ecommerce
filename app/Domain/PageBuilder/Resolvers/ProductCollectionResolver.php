<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Resolvers;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;

final readonly class ProductCollectionResolver
{
    public function __construct(
        private StorefrontProductDataFactory $productData,
    ) {}

    /**
     * Resolve a validated Product Collection section configuration
     * into storefront-safe product card data.
     *
     * @param  array<string, mixed>  $config
     * @return list<StorefrontProductCardData>
     */
    public function resolve(array $config): array
    {
        $limit = $this->resolveLimit($config);

        $source = $config['source'] ?? null;

        if (! is_array($source)) {
            throw new LogicException(
                'Product Collection configuration must contain a valid source.',
            );
        }

        $sourceType = CatalogSourceType::tryFrom(
            $source['type'] ?? '',
        );

        if ($sourceType === null) {
            throw new LogicException(
                'Product Collection configuration contains an unsupported source type.',
            );
        }

        $products = match ($sourceType) {
            CatalogSourceType::Latest => $this
                ->resolveLatest($limit),

            CatalogSourceType::Featured => $this
                ->resolveFeatured($limit),

            CatalogSourceType::Category => $this
                ->resolveCategory(
                    source: $source,
                    limit: $limit,
                ),

            CatalogSourceType::Manual => $this
                ->resolveManual(
                    source: $source,
                    limit: $limit,
                ),
        };

        return $products
            ->map(
                fn (Product $product): StorefrontProductCardData => $this
                    ->productData
                    ->card($product),
            )
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    private function resolveLatest(
        int $limit,
    ): Collection {
        return $this
            ->baseQuery()
            ->orderByDesc(
                'products.published_at',
            )
            ->orderByDesc(
                'products.id',
            )
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function resolveFeatured(
        int $limit,
    ): Collection {
        return $this
            ->baseQuery()
            ->where(
                'products.is_featured',
                true,
            )
            ->orderByDesc(
                'products.published_at',
            )
            ->orderByDesc(
                'products.id',
            )
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $source
     * @return Collection<int, Product>
     */
    private function resolveCategory(
        array $source,
        int $limit,
    ): Collection {
        $categoryId =
            $source['category_id'] ?? null;

        if (
            ! is_int($categoryId)
            || $categoryId < 1
        ) {
            throw new LogicException(
                'Category source must contain a valid category_id.',
            );
        }

        return $this
            ->baseQuery()
            ->whereHas(
                'categories',
                static fn (Builder $query) => $query
                    ->where(
                        'categories.id',
                        $categoryId,
                    ),
            )
            ->orderByDesc(
                'products.published_at',
            )
            ->orderByDesc(
                'products.id',
            )
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $source
     * @return Collection<int, Product>
     */
    private function resolveManual(
        array $source,
        int $limit,
    ): Collection {
        $productIds =
            $source['product_ids'] ?? null;

        if (! is_array($productIds)) {
            throw new LogicException(
                'Manual source must contain product_ids.',
            );
        }

        /** @var list<int> $productIds */
        $limitedIds = array_slice(
            $productIds,
            0,
            $limit,
        );

        if ($limitedIds === []) {
            return collect();
        }

        $products = $this
            ->baseQuery()
            ->whereIn(
                'products.id',
                $limitedIds,
            )
            ->get()
            ->keyBy(
                static fn (
                    Product $product,
                ): int => $product->id,
            );

        /*
         * whereIn() does not guarantee the configured order.
         *
         * Rebuild from product_ids so manual Product Collections
         * follow the exact order selected in Page Builder.
         *
         * Products that are no longer publicly available are omitted.
         */
        return collect($limitedIds)
            ->map(
                static fn (
                    int $productId,
                ): ?Product => $products
                    ->get($productId),
            )
            ->filter(
                static fn (
                    ?Product $product,
                ): bool => $product
                    instanceof Product,
            )
            ->values();
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(): Builder
    {
        return Product::query()
            ->published()
            ->with([
                'brand:id,name,slug',

                'primaryImage',

                'variants' => static fn ($query) => $query
                    ->where(
                        'is_active',
                        true,
                    )
                    ->select([
                        'id',
                        'product_id',
                        'price',
                        'compare_at_price',
                        'cost_price',
                        'is_active',
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveLimit(
        array $config,
    ): int {
        $limit = $config['limit'] ?? null;

        if (
            ! is_int($limit)
            || $limit < 1
        ) {
            throw new LogicException(
                'Product Collection configuration must contain a valid limit.',
            );
        }

        return $limit;
    }
}
