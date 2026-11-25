<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Sitemap;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Sources\ProductSitemapSource;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductSitemapSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings(
            robotsIndex: true,
        );
    }

    public function test_it_reports_product_sitemap_type(): void
    {
        $source =
            app(
                ProductSitemapSource::class,
            );

        $this->assertSame(
            SitemapType::Products,
            $source->type(),
        );
    }

    public function test_published_indexable_product_is_included(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create([
                    'slug' => 'indexable-product',
                ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            "https://example.com/products/{$product->slug}",
            $urls[0]->url,
        );
    }

    public function test_draft_product_is_excluded(): void
    {
        Product::factory()
            ->create([
                'status' => ProductStatus::Draft,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_archived_product_is_excluded(): void
    {
        Product::factory()
            ->create([
                'status' => ProductStatus::Archived,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_future_published_product_is_excluded(): void
    {
        Product::factory()
            ->create([
                'status' => ProductStatus::Published,

                'published_at' => now()
                    ->addDay(),
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_product_with_explicit_noindex_is_excluded(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create();

        $product
            ->seoMeta()
            ->create([
                'robots_index' => false,
            ]);

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_product_with_explicit_index_is_included_when_global_default_is_noindex(): void
    {
        $this->saveSeoSettings(
            robotsIndex: false,
        );

        $product =
            Product::factory()
                ->published()
                ->create([
                    'slug' => 'explicit-index-product',
                ]);

        $product
            ->seoMeta()
            ->create([
                'robots_index' => true,
            ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://example.com/products/explicit-index-product',
            $urls[0]->url,
        );
    }

    public function test_product_without_robot_override_inherits_global_noindex(): void
    {
        $this->saveSeoSettings(
            robotsIndex: false,
        );

        Product::factory()
            ->published()
            ->create();

        $this->assertSame(
            [],
            $this->urls(),
        );
    }

    public function test_canonical_override_is_used(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create();

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://canonical.example.com/custom-product',
            ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertSame(
            'https://canonical.example.com/custom-product',
            $urls[0]->url,
        );
    }

    public function test_last_modified_uses_product_updated_at(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create([
                    'updated_at' => '2026-11-17 14:30:00',
                ]);

        $urls =
            $this->urls();

        $this->assertCount(
            1,
            $urls,
        );

        $this->assertNotNull(
            $urls[0]
                ->lastModified,
        );

        $this->assertSame(
            $product
                ->updated_at
                ?->toImmutable()
                ->getTimestamp(),

            $urls[0]
                ->lastModified
                ?->getTimestamp(),
        );
    }

    public function test_products_are_returned_in_deterministic_id_order(): void
    {
        $first =
            Product::factory()
                ->published()
                ->create([
                    'slug' => 'first-product',
                ]);

        $second =
            Product::factory()
                ->published()
                ->create([
                    'slug' => 'second-product',
                ]);

        $urls =
            $this->urls();

        $this->assertSame(
            [
                "https://example.com/products/{$first->slug}",
                "https://example.com/products/{$second->slug}",
            ],
            array_map(
                static fn (
                    $url,
                ): string => $url->url,

                $urls,
            ),
        );
    }

    /**
     * @return list<SitemapUrlData>
     */
    private function urls(): array
    {
        return iterator_to_array(
            app(
                ProductSitemapSource::class,
            )->urls(),

            false,
        );
    }

    private function saveSeoSettings(
        bool $robotsIndex,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: $robotsIndex,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
