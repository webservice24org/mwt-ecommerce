<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\CatalogCategoryOptionData;
use App\Models\Category;

final class GetCatalogCategoryOptionsQuery
{
    /**
     * @return list<CatalogCategoryOptionData>
     */
    public function handle(): array
    {
        return Category::query()
            ->select([
                'id',
                'name',
                'slug',
            ])
            ->orderBy('name')
            ->get()
            ->map(
                static fn (Category $category): CatalogCategoryOptionData => new CatalogCategoryOptionData(
                    id: $category->id,
                    name: $category->name,
                    slug: $category->slug,
                ),
            )
            ->all();
    }
}
