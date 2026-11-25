<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\SeoEntitySourceFactory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoEntitySourceFactoryTest extends TestCase
{
    use RefreshDatabase;

    private SeoEntitySourceFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory =
            app(
                SeoEntitySourceFactory::class,
            );
    }

    public function test_product_source_uses_legacy_meta_fields_and_short_description(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Test Product',

                    'short_description' => 'Short product description.',

                    'description' => '<p>Long product description.</p>',

                    'meta_title' => 'Product SEO Title',

                    'meta_description' => 'Product SEO Description',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertSame(
            'Test Product',
            $source->pageTitle,
        );

        $this->assertSame(
            'Product SEO Title',
            $source->metaTitle,
        );

        $this->assertSame(
            'Product SEO Description',
            $source->metaDescription,
        );

        $this->assertSame(
            'Short product description.',
            $source->fallbackDescription,
        );
    }

    public function test_product_description_falls_back_to_plain_text_full_description(): void
    {
        $product =
            Product::factory()
                ->create([
                    'short_description' => null,

                    'description' => '<p>Powerful <strong>gaming</strong> laptop.</p>',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertSame(
            'Powerful gaming laptop.',
            $source->fallbackDescription,
        );
    }

    public function test_product_uses_primary_product_image_as_fallback_image(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductImage::factory()
            ->for(
                $product,
            )
            ->create([
                'path' => 'catalog/products/test-product.jpg',

                'is_primary' => true,

                'position' => 0,
            ]);

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertNotNull(
            $source->fallbackImage,
        );

        $this->assertStringContainsString(
            '/storage/catalog/products/test-product.jpg',
            $source->fallbackImage,
        );
    }

    public function test_category_source_maps_category_fields(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Laptops',

                    'description' => 'Laptop computers and accessories.',

                    'image_path' => 'catalog/categories/laptops.jpg',

                    'meta_title' => 'Laptop Category',

                    'meta_description' => 'Browse laptop computers.',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $category,
                );

        $this->assertSame(
            'Laptops',
            $source->pageTitle,
        );

        $this->assertSame(
            'Laptop Category',
            $source->metaTitle,
        );

        $this->assertSame(
            'Browse laptop computers.',
            $source->metaDescription,
        );

        $this->assertSame(
            'Laptop computers and accessories.',
            $source->fallbackDescription,
        );

        $this->assertNotNull(
            $source->fallbackImage,
        );

        $this->assertStringContainsString(
            '/storage/catalog/categories/laptops.jpg',
            $source->fallbackImage,
        );
    }

    public function test_brand_source_maps_brand_fields(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Example Brand',

                    'description' => 'Example brand description.',

                    'logo_path' => 'catalog/brands/example.jpg',

                    'meta_title' => 'Example Brand SEO',

                    'meta_description' => 'Shop Example Brand.',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $brand,
                );

        $this->assertSame(
            'Example Brand',
            $source->pageTitle,
        );

        $this->assertSame(
            'Example Brand SEO',
            $source->metaTitle,
        );

        $this->assertSame(
            'Shop Example Brand.',
            $source->metaDescription,
        );

        $this->assertSame(
            'Example brand description.',
            $source->fallbackDescription,
        );

        $this->assertNotNull(
            $source->fallbackImage,
        );

        $this->assertStringContainsString(
            '/storage/catalog/brands/example.jpg',
            $source->fallbackImage,
        );
    }

    public function test_page_source_maps_page_fields_and_removes_html_from_content(): void
    {
        $page =
            Page::factory()
                ->create([
                    'title' => 'About Us',

                    'content' => '<p>Learn more about <strong>our company</strong>.</p>',

                    'featured_image' => 'pages/about.jpg',

                    'meta_title' => 'About MWT',

                    'meta_description' => 'About MicroWeb Technology.',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $page,
                );

        $this->assertSame(
            'About Us',
            $source->pageTitle,
        );

        $this->assertSame(
            'About MWT',
            $source->metaTitle,
        );

        $this->assertSame(
            'About MicroWeb Technology.',
            $source->metaDescription,
        );

        $this->assertSame(
            'Learn more about our company.',
            $source->fallbackDescription,
        );

        $this->assertNotNull(
            $source->fallbackImage,
        );

        $this->assertStringContainsString(
            '/storage/pages/about.jpg',
            $source->fallbackImage,
        );
    }

    public function test_shared_seo_overrides_are_mapped_into_entity_source(): void
    {
        $product =
            Product::factory()
                ->create();

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/custom-product',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Custom OG Title',

                'og_description' => 'Custom OG Description',

                'og_image' => 'https://example.com/custom-og.jpg',

                'twitter_title' => 'Custom Twitter Title',

                'twitter_description' => 'Custom Twitter Description',

                'twitter_image' => 'https://example.com/twitter.jpg',
            ]);

        $source =
            $this
                ->factory
                ->from(
                    $product->fresh(),
                );

        $this->assertSame(
            'https://example.com/custom-product',
            $source
                ->overrides
                ->canonicalUrl,
        );

        $this->assertFalse(
            $source
                ->overrides
                ->robotsIndex,
        );

        $this->assertTrue(
            $source
                ->overrides
                ->robotsFollow,
        );

        $this->assertSame(
            'Custom OG Title',
            $source
                ->overrides
                ->ogTitle,
        );

        $this->assertSame(
            'https://example.com/custom-og.jpg',
            $source
                ->overrides
                ->ogImage,
        );

        $this->assertSame(
            'Custom Twitter Title',
            $source
                ->overrides
                ->twitterTitle,
        );
    }

    public function test_missing_shared_seo_row_produces_empty_overrides(): void
    {
        $product =
            Product::factory()
                ->create();

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertNull(
            $source
                ->overrides
                ->canonicalUrl,
        );

        $this->assertNull(
            $source
                ->overrides
                ->robotsIndex,
        );

        $this->assertNull(
            $source
                ->overrides
                ->robotsFollow,
        );

        $this->assertNull(
            $source
                ->overrides
                ->ogTitle,
        );

        $this->assertNull(
            $source
                ->overrides
                ->twitterImage,
        );
    }

    public function test_absolute_product_image_url_is_preserved(): void
    {
        $product =
            Product::factory()
                ->create();

        ProductImage::factory()
            ->for(
                $product,
            )
            ->create([
                'path' => 'https://cdn.example.com/products/product.jpg',

                'is_primary' => true,

                'position' => 0,
            ]);

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertSame(
            'https://cdn.example.com/products/product.jpg',
            $source->fallbackImage,
        );
    }

    public function test_blank_entity_image_produces_null_fallback_image(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'logo_path' => null,
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $brand,
                );

        $this->assertNull(
            $source->fallbackImage,
        );
    }

    public function test_blank_descriptions_do_not_block_global_description_fallback(): void
    {
        $product =
            Product::factory()
                ->create([
                    'short_description' => '   ',

                    'description' => '<p>   </p>',
                ]);

        $source =
            $this
                ->factory
                ->from(
                    $product,
                );

        $this->assertNull(
            $source->fallbackDescription,
        );
    }
}
