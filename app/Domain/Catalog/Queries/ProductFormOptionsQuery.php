<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\Brand;
use App\Models\Category;

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
    public function get(): array
    {
        $brands = Brand::query()
            ->where('is_active', true)
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
            ->where('is_active', true)
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
