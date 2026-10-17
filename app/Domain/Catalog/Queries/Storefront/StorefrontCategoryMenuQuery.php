<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Category;
use Illuminate\Support\Collection;

final readonly class StorefrontCategoryMenuQuery
{
    private const ROOT_LIMIT = 6;

    private const CHILD_LIMIT = 12;

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     children: array<int, array{
     *         id: int,
     *         name: string,
     *         slug: string,
     *         children: array<int, mixed>
     *     }>
     * }>
     */
    public function get(): array
    {
        $categories = collect(
            Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get([
                    'id',
                    'parent_id',
                    'name',
                    'slug',
                    'position',
                ])
                ->all(),
        );

        if ($categories->isEmpty()) {
            return [];
        }

        $childrenByParent = $categories
            ->filter(
                static fn (Category $category): bool => $category->parent_id !== null,
            )
            ->groupBy(
                static fn (Category $category): int => (int) $category->parent_id,
            );

        return $categories
            ->filter(
                static fn (Category $category): bool => $category->parent_id === null,
            )
            ->take(self::ROOT_LIMIT)
            ->map(
                fn (Category $category): array => $this->mapRoot(
                    $category,
                    $childrenByParent,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int|string, Collection<int, Category>>  $childrenByParent
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     children: array<int, array{
     *         id: int,
     *         name: string,
     *         slug: string,
     *         children: array<int, mixed>
     *     }>
     * }
     */
    private function mapRoot(
        Category $category,
        Collection $childrenByParent,
    ): array {
        $children = $childrenByParent
            ->get(
                $category->id,
                collect(),
            )
            ->take(self::CHILD_LIMIT)
            ->map(
                static fn (Category $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'children' => [],
                ],
            )
            ->values()
            ->all();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'children' => $children,
        ];
    }
}
