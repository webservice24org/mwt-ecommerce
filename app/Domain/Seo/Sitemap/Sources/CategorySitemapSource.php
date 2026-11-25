<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Sources;

use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Models\Category;
use App\Models\SeoMeta;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final readonly class CategorySitemapSource implements SitemapSource
{
    public function __construct(
        private CanonicalUrlResolver $canonicalUrlResolver,
        private SeoSettingsService $seoSettings,
    ) {}

    public function type(): SitemapType
    {
        return SitemapType::Categories;
    }

    /**
     * @return Generator<int, SitemapUrlData>
     */
    public function urls(): Generator
    {
        $settings =
            $this
                ->seoSettings
                ->get();

        foreach (
            $this
                ->query()
                ->lazyById() as $category
        ) {
            $relation =
                $category->getRelation(
                    'seoMeta',
                );

            $seoMeta =
                $relation instanceof SeoMeta
                ? $relation
                : null;

            $robotsIndex =
                $settings
                    ->defaultRobotsIndex;

            if (
                $seoMeta instanceof SeoMeta
                && $seoMeta->robots_index !== null
            ) {
                $robotsIndex =
                    $seoMeta->robots_index;
            }

            if (! $robotsIndex) {
                continue;
            }

            $canonicalUrl =
                $this
                    ->canonicalUrlResolver
                    ->resolve(
                        canonicalOverride: $seoMeta instanceof SeoMeta
                        ? $seoMeta->canonical_url
                        : null,

                        path: "/category/{$category->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
                    );

            yield new SitemapUrlData(
                url: $canonicalUrl,

                lastModified: $category
                    ->updated_at
                    ?->toImmutable(),
            );
        }
    }

    /**
     * @return Builder<Category>
     */
    private function query(): Builder
    {
        return Category::query()
            ->select([
                'id',
                'slug',
                'is_active',
                'updated_at',
            ])
            ->with([
                'seoMeta:id,seoable_type,seoable_id,canonical_url,robots_index',
            ])
            ->where(
                'is_active',
                true,
            )
            ->orderBy(
                'id',
            );
    }
}
