<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final class ProductFormOptionsQuery
{
    /**
     * @return array{
     *     brands: list<array{
     *         id: int,
     *         name: string
     *     }>,
     *     categories: list<array{
     *         id: int,
     *         name: string,
     *         parent_id: int|null
     *     }>
     * }
     */
    public function get(
        ?Product $product = null,
    ): array {
        $brandId = $product?->brand_id;

        $categoryIds = $product === null
            ? []
            : $product
                ->categories()
                ->pluck('categories.id')
                ->map(
                    static fn (mixed $id): int => (int) $id,
                )
                ->all();

        $brands = Brand::query()
            ->where(
                static function (Builder $query) use ($brandId): void {
                    $query->where('is_active', true);

                    if ($brandId !== null) {
                        $query->orWhere(
                            'id',
                            $brandId,
                        );
                    }
                },
            )
            ->orderBy('position')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ])
            ->map(
                static fn (Brand $brand): array => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ],
            )
            ->values()
            ->all();

        $categories = Category::query()
            ->where(
                static function (Builder $query) use ($categoryIds): void {
                    $query->where('is_active', true);

                    if ($categoryIds !== []) {
                        $query->orWhereIn(
                            'id',
                            $categoryIds,
                        );
                    }
                },
            )
            ->orderBy('position')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'parent_id',
            ])
            ->map(
                static fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                ],
            )
            ->values()
            ->all();

        return [
            'brands' => $brands,
            'categories' => $categories,
        ];
    }
}
