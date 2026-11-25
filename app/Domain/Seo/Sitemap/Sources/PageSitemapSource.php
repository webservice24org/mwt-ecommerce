<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Sources;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Models\Page;
use App\Models\SeoMeta;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final readonly class PageSitemapSource implements SitemapSource
{
    public function __construct(
        private CanonicalUrlResolver $canonicalUrlResolver,
        private SeoSettingsService $seoSettings,
    ) {}

    public function type(): SitemapType
    {
        return SitemapType::Pages;
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
                ->lazyById() as $page
        ) {
            $relation =
                $page->getRelation(
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

                        path: "/pages/{$page->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
                    );

            yield new SitemapUrlData(
                url: $canonicalUrl,

                lastModified: $page
                    ->updated_at
                    ?->toImmutable(),
            );
        }
    }

    /**
     * @return Builder<Page>
     */
    private function query(): Builder
    {
        return Page::query()
            ->select([
                'id',
                'slug',
                'status',
                'published_at',
                'updated_at',
            ])
            ->with([
                'seoMeta:id,seoable_type,seoable_id,canonical_url,robots_index',
            ])
            ->where(
                'status',
                PageStatus::Published->value,
            )
            ->where(
                static function ($query): void {
                    $query
                        ->whereNull(
                            'published_at',
                        )
                        ->orWhere(
                            'published_at',
                            '<=',
                            now(),
                        );
                },
            )
            ->orderBy(
                'id',
            );
    }
}
