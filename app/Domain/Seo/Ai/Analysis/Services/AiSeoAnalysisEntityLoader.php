<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

final class AiSeoAnalysisEntityLoader
{
    public function product(
        int $entityId,
    ): Product {
        return Product::query()
            ->with([
                'brand',
                'categories',
                'seoMeta',
                'primaryImage',
                'featuredImage',
            ])
            ->findOrFail(
                $entityId,
            );
    }

    public function category(
        int $entityId,
    ): Category {
        return Category::query()
            ->with([
                'parent',
                'seoMeta',
            ])
            ->findOrFail(
                $entityId,
            );
    }

    public function brand(
        int $entityId,
    ): Brand {
        return Brand::query()
            ->with([
                'seoMeta',
            ])
            ->findOrFail(
                $entityId,
            );
    }

    public function page(
        int $entityId,
    ): Page {
        return Page::query()
            ->with([
                'enabledSections',
                'seoMeta',
            ])
            ->findOrFail(
                $entityId,
            );
    }
}
