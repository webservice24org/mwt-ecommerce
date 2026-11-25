<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoEntitySnapshotBuilderTest extends TestCase
{
    use RefreshDatabase;

    private AiSeoEntitySnapshotBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder =
            app(
                AiSeoEntitySnapshotBuilder::class,
            );
    }

    public function test_product_snapshot_contains_fact_context_and_safe_current_seo_only(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Acme',
                ]);

        $category =
            Category::factory()
                ->create([
                    'name' => 'Cables',
                ]);

        $product =
            Product::factory()
                ->create([
                    'brand_id' => $brand->id,

                    'name' => 'Braided USB Cable',

                    'slug' => 'braided-usb-cable',

                    'short_description' => '<p>Fast &amp; durable charging cable.</p>',

                    'description' => '<p>Designed for everyday charging.</p>',

                    'meta_title' => 'Existing Cable Title',

                    'meta_description' => 'Existing cable description.',
                ]);

        $product
            ->categories()
            ->sync([
                $category->id,
            ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/hidden-canonical',

                'robots_index' => false,

                'og_title' => 'Existing OG Cable',

                'og_description' => 'Existing OG description.',

                'og_image' => 'https://example.com/hidden-og.jpg',

                'twitter_title' => 'Existing X Cable',

                'twitter_description' => 'Existing X description.',

                'twitter_image' => 'https://example.com/hidden-x.jpg',
            ]);

        $snapshot =
            $this
                ->builder
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        self::assertSame(
            'product',
            $snapshot['entity']['type'],
        );

        self::assertSame(
            'Fast & durable charging cable.',
            $snapshot['content']['summary'],
        );

        self::assertSame(
            'Acme',
            $snapshot['context']['brand'],
        );

        self::assertSame(
            [
                'Cables',
            ],
            $snapshot['context']['categories'],
        );

        self::assertSame(
            'Existing Cable Title',
            $snapshot['current_seo']['meta_title'],
        );

        self::assertSame(
            'Existing OG Cable',
            $snapshot['current_seo']['og_title'],
        );

        self::assertSame(
            'Existing X Cable',
            $snapshot['current_seo']['twitter_title'],
        );

        self::assertArrayNotHasKey(
            'canonical_url',
            $snapshot['current_seo'],
        );

        self::assertArrayNotHasKey(
            'robots_index',
            $snapshot['current_seo'],
        );

        self::assertArrayNotHasKey(
            'og_image',
            $snapshot['current_seo'],
        );

        self::assertArrayNotHasKey(
            'twitter_image',
            $snapshot['current_seo'],
        );
    }

    public function test_category_snapshot_includes_parent_name_and_description(): void
    {
        $parent =
            Category::factory()
                ->create([
                    'name' => 'Electronics',
                ]);

        $category =
            Category::factory()
                ->create([
                    'parent_id' => $parent->id,

                    'name' => 'Laptops',

                    'slug' => 'laptops',

                    'description' => '<p>Portable computers.</p>',
                ]);

        $snapshot =
            $this
                ->builder
                ->build(
                    AiSeoEntityType::Category,
                    $category->id,
                )
                ->toArray();

        self::assertSame(
            'Portable computers.',
            $snapshot['content']['body'],
        );

        self::assertSame(
            'Electronics',
            $snapshot['context']['parent_category'],
        );
    }

    public function test_brand_snapshot_contains_only_brand_facts_and_safe_seo_text(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Example Brand',

                    'slug' => 'example-brand',

                    'description' => '<p>Everyday electronics.</p>',

                    'meta_title' => 'Example Brand SEO',
                ]);

        $snapshot =
            $this
                ->builder
                ->build(
                    AiSeoEntityType::Brand,
                    $brand->id,
                )
                ->toArray();

        self::assertSame(
            'Everyday electronics.',
            $snapshot['content']['body'],
        );

        self::assertSame(
            [],
            $snapshot['context'],
        );

        self::assertSame(
            'Example Brand SEO',
            $snapshot['current_seo']['meta_title'],
        );
    }

    public function test_page_snapshot_includes_classic_and_enabled_builder_text_but_excludes_urls(): void
    {
        $page =
            Page::factory()
                ->create([
                    'title' => 'About Us',

                    'slug' => 'about-us',

                    'status' => PageStatus::Published,

                    'content_mode' => PageContentMode::Builder,

                    'content' => '<p>Our company story.</p>',

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::Hero,

                'template' => 'static',

                'position' => 0,

                'is_enabled' => true,

                'layout' => [],

                'config' => [
                    'title' => 'Trusted Technology',

                    'description' => 'Serving customers with practical products.',

                    'background_image' => '/storage/about.jpg',

                    'primary_button' => [
                        'label' => 'Shop Now',

                        'url' => '/products',
                    ],
                ],
            ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::Hero,

                'template' => 'static',

                'position' => 1,

                'is_enabled' => false,

                'layout' => [],

                'config' => [
                    'title' => 'Hidden Builder Copy',
                ],
            ]);

        $snapshot =
            $this
                ->builder
                ->build(
                    AiSeoEntityType::Page,
                    $page->id,
                )
                ->toArray();

        self::assertSame(
            'Our company story.',
            $snapshot['content']['body'],
        );

        self::assertStringContainsString(
            'Trusted Technology',
            (string) $snapshot[
                'content'
            ]['builder_text'],
        );

        self::assertStringContainsString(
            'Serving customers with practical products.',
            (string) $snapshot[
                'content'
            ]['builder_text'],
        );

        self::assertStringNotContainsString(
            '/storage/about.jpg',
            (string) $snapshot[
                'content'
            ]['builder_text'],
        );

        self::assertStringNotContainsString(
            '/products',
            (string) $snapshot[
                'content'
            ]['builder_text'],
        );

        self::assertStringNotContainsString(
            'Hidden Builder Copy',
            (string) $snapshot[
                'content'
            ]['builder_text'],
        );
    }
}
