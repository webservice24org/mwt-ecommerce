<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Category;

final readonly class StorefrontCategoryQuery
{
    public function findBySlugOrFail(
        string $slug,
    ): Category {
        return Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
