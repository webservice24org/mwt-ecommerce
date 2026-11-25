<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries\Storefront;

use App\Models\Brand;

final readonly class StorefrontBrandQuery
{
    public function findBySlugOrFail(
        string $slug,
    ): Brand {
        return Brand::query()
            ->with([
                'seoMeta',
            ])
            ->where(
                'slug',
                $slug,
            )
            ->where(
                'is_active',
                true,
            )
            ->firstOrFail();
    }
}
