<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created(): void
    {
        $product = Product::factory()
            ->create();

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'status' => ProductStatus::Draft->value,
            ],
        );
    }

    public function test_product_can_belong_to_brand(): void
    {
        $brand = Brand::factory()->create();

        $product = Product::factory()
            ->create([
                'brand_id' => $brand->id,
            ]);

        $this->assertTrue(
            $product->brand->is($brand),
        );
    }

    public function test_product_can_have_categories(): void
    {
        $product = Product::factory()->create();

        $categories = Category::factory()
            ->count(2)
            ->create();

        $product->categories()->attach(
            $categories->modelKeys(),
        );

        $this->assertCount(
            2,
            $product->fresh()->categories,
        );
    }

    public function test_product_can_have_variants(): void
    {
        $product = Product::factory()->create();

        ProductVariant::factory()
            ->count(2)
            ->create([
                'product_id' => $product->id,
            ]);

        $this->assertCount(
            2,
            $product->fresh()->variants,
        );
    }

    public function test_product_can_have_images(): void
    {
        $product = Product::factory()->create();

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test.webp',
            'alt_text' => 'Test',
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->assertCount(
            1,
            $product->fresh()->images,
        );
    }

    public function test_published_product_is_public_when_publication_time_is_reached(): void
    {
        $product = Product::factory()
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $this->assertTrue(
            $product->isPublished(),
        );
    }

    public function test_future_product_is_not_yet_public(): void
    {
        $product = Product::factory()
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->addDay(),
            ]);

        $this->assertFalse(
            $product->isPublished(),
        );
    }

    public function test_draft_product_is_not_public(): void
    {
        $product = Product::factory()
            ->create([
                'status' => ProductStatus::Draft,
            ]);

        $this->assertFalse(
            $product->isPublished(),
        );
    }
}
