<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Models\Category;
use Illuminate\Support\Collection;

final class CategoryParentOptionsQuery
{
    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function forCreate(): Collection
    {
        return Category::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(
                fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            );
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function forEdit(
        Category $category,
    ): Collection {
        $categories = Category::query()
            ->select([
                'id',
                'parent_id',
                'name',
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        /** @var array<int, true> $excluded */
        $excluded = [
            $category->id => true,
        ];

        /** @var list<int> $currentLevel */
        $currentLevel = [
            $category->id,
        ];

        while ($currentLevel !== []) {
            /** @var list<int> $nextLevel */
            $nextLevel = [];

            foreach ($categories as $candidate) {
                if (
                    $candidate->parent_id !== null
                    && in_array(
                        $candidate->parent_id,
                        $currentLevel,
                        true,
                    )
                    && ! isset($excluded[$candidate->id])
                ) {
                    $excluded[$candidate->id] = true;
                    $nextLevel[] = $candidate->id;
                }
            }

            $currentLevel = $nextLevel;
        }

        return $categories
            ->reject(
                fn (Category $candidate): bool => isset(
                    $excluded[$candidate->id],
                ),
            )
            ->values()
            ->map(
                fn (Category $candidate): array => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                ],
            );
    }
}
