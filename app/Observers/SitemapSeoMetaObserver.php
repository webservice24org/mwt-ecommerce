<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Seo\Sitemap\Services\SitemapInvalidator;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;

final readonly class SitemapSeoMetaObserver
{
    /**
     * @var list<class-string>
     */
    private const SITEMAP_ENTITY_TYPES = [
        Product::class,
        Category::class,
        Brand::class,
        Page::class,
    ];

    public function __construct(
        private SitemapInvalidator $sitemap,
    ) {}

    public function created(
        SeoMeta $seoMeta,
    ): void {
        $this->invalidateIfRelevant(
            $seoMeta,
        );
    }

    public function updated(
        SeoMeta $seoMeta,
    ): void {
        $this->invalidateIfRelevant(
            $seoMeta,
        );
    }

    public function deleted(
        SeoMeta $seoMeta,
    ): void {
        $this->invalidateIfRelevant(
            $seoMeta,
        );
    }

    private function invalidateIfRelevant(
        SeoMeta $seoMeta,
    ): void {
        if (
            ! in_array(
                $seoMeta->seoable_type,
                self::SITEMAP_ENTITY_TYPES,
                true,
            )
        ) {
            return;
        }

        $this
            ->sitemap
            ->invalidate();
    }
}
