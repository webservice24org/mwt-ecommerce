<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\Product;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UniqueSlugGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_slug_from_value(): void
    {
        $slug = app(
            UniqueSlugGenerator::class,
        )->generate(
            table: 'products',
            value: 'Samsung Galaxy S26 Ultra',
        );

        $this->assertSame(
            'samsung-galaxy-s26-ultra',
            $slug,
        );
    }

    public function test_it_adds_suffix_when_slug_exists(): void
    {
        Product::factory()->create([
            'slug' => 'test-product',
        ]);

        $slug = app(
            UniqueSlugGenerator::class,
        )->generate(
            table: 'products',
            value: 'Test Product',
        );

        $this->assertSame(
            'test-product-2',
            $slug,
        );
    }

    public function test_it_finds_next_available_suffix(): void
    {
        Product::factory()->create([
            'slug' => 'test-product',
        ]);

        Product::factory()->create([
            'slug' => 'test-product-2',
        ]);

        $slug = app(
            UniqueSlugGenerator::class,
        )->generate(
            table: 'products',
            value: 'Test Product',
        );

        $this->assertSame(
            'test-product-3',
            $slug,
        );
    }

    public function test_it_can_ignore_current_record(): void
    {
        $product = Product::factory()->create([
            'slug' => 'test-product',
        ]);

        $slug = app(
            UniqueSlugGenerator::class,
        )->generate(
            table: 'products',
            value: 'Test Product',
            ignoreId: $product->id,
        );

        $this->assertSame(
            'test-product',
            $slug,
        );
    }

    public function test_empty_slug_uses_item_fallback(): void
    {
        $slug = app(
            UniqueSlugGenerator::class,
        )->generate(
            table: 'products',
            value: '---',
        );

        $this->assertSame(
            'item',
            $slug,
        );
    }
}
