<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Exceptions\InvalidCategoryHierarchyException;
use App\Models\Category;

final class CategoryHierarchyService
{
    public function ensureParentExists(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if (! Category::query()->whereKey($parentId)->exists()) {
            throw new InvalidCategoryHierarchyException(
                'The selected parent category does not exist.',
            );
        }
    }

    public function ensureCanAssignParent(
        Category $category,
        ?int $parentId,
    ): void {
        if ($parentId === null) {
            return;
        }

        if ($category->id === $parentId) {
            throw new InvalidCategoryHierarchyException(
                'A category cannot be its own parent.',
            );
        }

        $currentParentId = $parentId;

        while ($currentParentId !== null) {
            if ($currentParentId === $category->id) {
                throw new InvalidCategoryHierarchyException(
                    'A category cannot be moved inside one of its descendants.',
                );
            }

            $parent = Category::query()
                ->select([
                    'id',
                    'parent_id',
                ])
                ->find($currentParentId);

            if ($parent === null) {
                throw new InvalidCategoryHierarchyException(
                    'The selected parent category does not exist.',
                );
            }

            $currentParentId = $parent->parent_id;
        }
    }
}
