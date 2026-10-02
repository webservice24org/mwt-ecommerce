<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Models\Category;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSectionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_products_section_is_resolved(): void
    {
        $page = Page::factory()->create();

        $product = Product::factory()->create([
            'is_featured' => true,
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::FeaturedProducts,
            'template' => 'grid',
            'config' => [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            'position' => 1,
            'is_enabled' => true,
        ]);

        $resolver = app(
            PageSectionResolver::class,
        );

        $resolved = $resolver->resolve(
            $section,
        );

        $this->assertInstanceOf(
            ResolvedPageSectionData::class,
            $resolved,
        );

        $this->assertSame(
            $section->id,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::FeaturedProducts,
            $resolved->type,
        );

        $this->assertSame(
            'grid',
            $resolved->template,
        );

        $this->assertSame(
            'Featured Products',
            $resolved->config['title'],
        );

        $this->assertArrayHasKey(
            'products',
            $resolved->data,
        );

        $this->assertCount(
            1,
            $resolved->data['products'],
        );

        $this->assertSame(
            $product->id,
            $resolved->data['products'][0]->id,
        );
    }

    public function test_resolved_section_serializes_to_public_contract(): void
    {
        $page = Page::factory()->create();

        Product::factory()->create([
            'is_featured' => true,
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::FeaturedProducts,
            'template' => 'grid',
            'config' => [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            'position' => 1,
            'is_enabled' => true,
        ]);

        $resolved = app(
            PageSectionResolver::class,
        )->resolve($section);

        $data = $resolved->toArray();

        $this->assertSame(
            $section->id,
            $data['id'],
        );

        $this->assertSame(
            'featured_products',
            $data['type'],
        );

        $this->assertSame(
            'grid',
            $data['template'],
        );

        $this->assertArrayHasKey(
            'config',
            $data,
        );

        $this->assertArrayHasKey(
            'data',
            $data,
        );

        $this->assertArrayNotHasKey(
            'position',
            $data,
        );

        $this->assertArrayNotHasKey(
            'is_enabled',
            $data,
        );
    }

    public function test_hero_section_is_resolved_without_external_data(): void
    {
        $section = new PageSection([
            'type' => SectionType::Hero,
            'template' => 'static',
            'config' => [
                'autoplay' => false,
                'autoplay_delay' => 5000,
                'effect' => 'fade',
                'show_arrows' => false,
                'show_dots' => false,
                'slides' => [
                    [
                        'background_color' => '#ffffff',
                        'background_image' => null,
                        'top_title' => 'Welcome',
                        'title' => 'Our Store',
                        'description' => 'Discover our latest products.',
                        'alignment' => 'center',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                ],
            ],
        ]);

        $section->id = 123;

        $resolved = app(
            PageSectionResolver::class,
        )->resolve($section);

        $this->assertSame(
            123,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::Hero,
            $resolved->type,
        );

        $this->assertSame(
            'static',
            $resolved->template,
        );

        $this->assertSame(
            $section->config,
            $resolved->config,
        );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }

    public function test_product_categories_section_is_resolved_in_configured_order(): void
    {
        $page = Page::factory()->create();

        $first = Category::factory()->create([
            'name' => 'First Category',
            'slug' => 'first-category',
            'is_active' => true,
        ]);

        $second = Category::factory()->create([
            'name' => 'Second Category',
            'slug' => 'second-category',
            'is_active' => true,
        ]);

        $third = Category::factory()->create([
            'name' => 'Third Category',
            'slug' => 'third-category',
            'is_active' => true,
        ]);

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::ProductCategories,
            'template' => 'grid',
            'config' => [
                'title' => 'Shop by Category',
                'category_ids' => [
                    $third->id,
                    $first->id,
                    $second->id,
                ],
                'show_name' => true,
                'columns' => 4,
                'show_product_count' => false,
            ],
            'position' => 1,
            'is_enabled' => true,
        ]);

        $resolved = app(
            PageSectionResolver::class,
        )->resolve($section);

        $this->assertSame(
            SectionType::ProductCategories,
            $resolved->type,
        );

        $this->assertSame(
            'grid',
            $resolved->template,
        );

        $this->assertArrayHasKey(
            'categories',
            $resolved->data,
        );

        $categories =
            $resolved->data['categories'];

        $this->assertCount(
            3,
            $categories,
        );

        $this->assertSame(
            [
                $third->id,
                $first->id,
                $second->id,
            ],
            array_map(
                static fn ($category): int => $category->id,
                $categories,
            ),
        );

        $this->assertNull(
            $categories[0]->productCount,
        );
    }

    public function test_product_categories_omits_unavailable_categories(): void
    {
        $page = Page::factory()->create();

        $active = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactive = Category::factory()->create([
            'is_active' => false,
        ]);

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::ProductCategories,
            'template' => 'grid',
            'config' => [
                'title' => 'Categories',
                'category_ids' => [
                    $inactive->id,
                    999999,
                    $active->id,
                ],
                'show_name' => true,
                'columns' => 4,
                'show_product_count' => false,
            ],
            'position' => 1,
            'is_enabled' => true,
        ]);

        $resolved = app(
            PageSectionResolver::class,
        )->resolve($section);

        $categories =
            $resolved->data['categories'];

        $this->assertCount(
            1,
            $categories,
        );

        $this->assertSame(
            $active->id,
            $categories[0]->id,
        );
    }

    public function test_product_categories_with_no_selected_categories_resolves_empty_data(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::ProductCategories,
            'template' => 'grid',
            'config' => [
                'title' => 'Categories',
                'category_ids' => [],
                'show_name' => true,
                'columns' => 4,
                'show_product_count' => false,
            ],
            'position' => 1,
            'is_enabled' => true,
        ]);

        $resolved = app(
            PageSectionResolver::class,
        )->resolve($section);

        $this->assertSame(
            [],
            $resolved->data['categories'],
        );
    }
}
