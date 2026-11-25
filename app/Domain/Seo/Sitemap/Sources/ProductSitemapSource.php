<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Sources;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Models\Product;
use App\Models\SeoMeta;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final readonly class ProductSitemapSource implements SitemapSource
{
    public function __construct(
        private CanonicalUrlResolver $canonicalUrlResolver,
        private SeoSettingsService $seoSettings,
    ) {}

    public function type(): SitemapType
    {
        return SitemapType::Products;
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
                ->lazyById() as $product
        ) {
            /*
             * The relation is eager loaded by query().
             *
             * getRelation() keeps the nullable runtime
             * relationship explicit instead of relying on
             * Eloquent's magic-property static type.
             */
            $relation =
                $product->getRelation(
                    'seoMeta',
                );

            $seoMeta =
                $relation instanceof SeoMeta
                ? $relation
                : null;

            /*
             * Entity override wins when explicitly set.
             *
             * Null means inherit the global robots policy.
             */
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

            /*
             * A sitemap must contain only indexable
             * public resources.
             */
            if (! $robotsIndex) {
                continue;
            }

            /*
             * Canonical URL resolution remains owned by
             * the authoritative CanonicalUrlResolver.
             *
             * Sitemap generation must not recreate
             * canonical precedence independently.
             */
            $canonicalUrl =
                $this
                    ->canonicalUrlResolver
                    ->resolve(
                        canonicalOverride: $seoMeta instanceof SeoMeta
                        ? $seoMeta->canonical_url
                        : null,

                        path: "/products/{$product->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
                    );

            yield new SitemapUrlData(
                url: $canonicalUrl,

                lastModified: $product
                    ->updated_at
                    ?->toImmutable(),
            );
        }
    }

    /**
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
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
                ProductStatus::Published->value,
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
