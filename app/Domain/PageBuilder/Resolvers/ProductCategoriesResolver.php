<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Resolvers;

use App\Domain\Catalog\Data\Storefront\StorefrontPageBuilderCategoryData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use LogicException;

final readonly class ProductCategoriesResolver
{
    /**
     * Resolve a validated Product Categories configuration
     * into storefront-safe category data.
     *
     * @param  array<string, mixed>  $config
     * @return list<StorefrontPageBuilderCategoryData>
     */
    public function resolve(
        array $config,
    ): array {
        $categoryIds =
            $this->resolveCategoryIds(
                $config,
            );

        if ($categoryIds === []) {
            return [];
        }

        $showProductCount =
            $this->resolveShowProductCount(
                $config,
            );

        $categories = $this
            ->categoryQuery(
                showProductCount: $showProductCount,
            )
            ->whereIn(
                'categories.id',
                $categoryIds,
            )
            ->get()
            ->keyBy(
                static fn (
                    Category $category,
                ): int => $category->id,
            );

        /*
         * whereIn() does not guarantee the
         * configured category order.
         *
         * Rebuild from category_ids so the
         * storefront follows the exact order
         * selected in Page Builder.
         *
         * Missing or inactive categories are
         * intentionally omitted.
         */
        return collect($categoryIds)
            ->map(
                fn (
                    int $categoryId,
                ): ?StorefrontPageBuilderCategoryData => $this
                    ->resolveCategoryData(
                        category: $categories->get(
                            $categoryId,
                        ),
                        showProductCount: $showProductCount,
                    ),
            )
            ->filter(
                static fn (
                    ?StorefrontPageBuilderCategoryData $category,
                ): bool => $category
                    instanceof StorefrontPageBuilderCategoryData,
            )
            ->values()
            ->all();
    }

    /**
     * @return Builder<Category>
     */
    private function categoryQuery(
        bool $showProductCount,
    ): Builder {
        $query = Category::query()
            ->where(
                'categories.is_active',
                true,
            );

        if (! $showProductCount) {
            return $query;
        }

        return $query->withCount([
            'products as published_products_count' => static function (
                Builder $query,
            ): void {
                /** @var Builder<Product> $query */
                $query->published();
            },
        ]);
    }

    private function resolveCategoryData(
        ?Category $category,
        bool $showProductCount,
    ): ?StorefrontPageBuilderCategoryData {
        if (! $category instanceof Category) {
            return null;
        }

        return new StorefrontPageBuilderCategoryData(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            imageUrl: $this->resolveImageUrl(
                $category,
            ),
            productCount: $showProductCount
                    ? $this->resolveProductCount(
                        $category,
                    )
                    : null,
        );
    }

    private function resolveImageUrl(
        Category $category,
    ): ?string {
        if (
            $category->image_path === null
            || $category->image_path === ''
        ) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url(
            $category->image_path,
        );
    }

    private function resolveProductCount(
        Category $category,
    ): int {
        $count = $category->getAttribute(
            'published_products_count',
        );

        if (! is_int($count)) {
            /*
             * Database drivers may expose aggregate
             * values as numeric strings.
             */
            if (
                is_string($count)
                && ctype_digit($count)
            ) {
                return (int) $count;
            }

            return 0;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<int>
     */
    private function resolveCategoryIds(
        array $config,
    ): array {
        $categoryIds =
            $config['category_ids']
            ?? null;

        if (! is_array($categoryIds)) {
            throw new LogicException(
                'Product Categories configuration must contain category_ids.',
            );
        }

        foreach (
            $categoryIds as $categoryId
        ) {
            if (
                ! is_int($categoryId)
                || $categoryId < 1
            ) {
                throw new LogicException(
                    'Product Categories configuration contains an invalid category ID.',
                );
            }
        }

        return $categoryIds;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveShowProductCount(
        array $config,
    ): bool {
        $showProductCount =
            $config[
                'show_product_count'
            ] ?? null;

        if (
            ! is_bool(
                $showProductCount,
            )
        ) {
            throw new LogicException(
                'Product Categories configuration must contain a valid show_product_count setting.',
            );
        }

        return $showProductCount;
    }
}
