<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
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
}
