<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Category;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheKey;

final readonly class StorefrontCategoryQuery
{
    public function __construct(
        private StorefrontCatalogCache $cache,
    ) {}

    public function findBySlugOrFail(
        string $slug,
    ): Category {
        $category = $this->cache->rememberNotNull(
            StorefrontCatalogCacheKey::category($slug),
            fn (): ?Category => $this->findActiveBySlug($slug),
        );

        abort_if(
            $category === null,
            404,
        );

        return $category;
    }

    private function findActiveBySlug(
        string $slug,
    ): ?Category {
        return Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
