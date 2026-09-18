<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_product_detail_page_is_public(): void
    {
        $product = Product::factory()->create([
            'name' => 'Public Product',
            'slug' => 'public-product',
            'sku' => 'PUBLIC-001',
            'price' => 15_000,
            'short_description' => 'Public short description.',
            'description' => 'Public product description.',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'meta_title' => 'Public Product SEO',
            'meta_description' => 'Public product meta description.',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Products/Show')
                    ->where('product.id', $product->id)
                    ->where('product.name', 'Public Product')
                    ->where('product.slug', 'public-product')
                    ->where('product.sku', 'PUBLIC-001')
                    ->where('product.pricing.price', 15_000)
                    ->where(
                        'product.meta_title',
                        'Public Product SEO',
                    )
                    ->where(
                        'product.meta_description',
                        'Public product meta description.',
                    ),
            );
    }

    public function test_draft_product_detail_returns_not_found(): void
    {
        $product = Product::factory()->create([
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertNotFound();
    }

    public function test_archived_product_detail_returns_not_found(): void
    {
        $product = Product::factory()->create([
            'slug' => 'archived-product',
            'status' => ProductStatus::Archived,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertNotFound();
    }

    public function test_future_scheduled_product_detail_returns_not_found(): void
    {
        $product = Product::factory()->create([
            'slug' => 'future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addHour(),
        ]);

        $this->get('/products/'.$product->slug)
            ->assertNotFound();
    }

    public function test_published_product_with_null_publication_date_is_public(): void
    {
        $product = Product::factory()->create([
            'slug' => 'immediate-product',
            'status' => ProductStatus::Published,
            'published_at' => null,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk();
    }

    public function test_product_detail_includes_public_brand_and_categories(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Example Brand',
            'slug' => 'example-brand',
            'is_active' => true,
        ]);

        $category = Category::factory()->create([
            'name' => 'Example Category',
            'slug' => 'example-category',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'slug' => 'related-product',
            'brand_id' => $brand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $product->categories()->attach($category);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.brand.slug',
                        'example-brand',
                    )
                    ->has('product.categories', 1)
                    ->where(
                        'product.categories.0.slug',
                        'example-category',
                    ),
            );
    }

    public function test_product_detail_only_exposes_active_variants(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'variable-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'ACTIVE-VARIANT',
            'price' => 20_000,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INACTIVE-VARIANT',
            'price' => 30_000,
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.variants', 1)
                    ->where(
                        'product.variants.0.sku',
                        'ACTIVE-VARIANT',
                    ),
            );
    }

    public function test_product_detail_does_not_expose_internal_commercial_fields(): void
    {
        $product = Product::factory()->create([
            'slug' => 'secure-product',
            'price' => 20_000,
            'cost_price' => 5_000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->missing('product.cost_price')
                    ->missing('product.costPrice')
                    ->missing('product.status')
                    ->missing('product.position')
                    ->missing('product.published_at'),
            );
    }

    public function test_variant_detail_does_not_expose_cost_or_barcode(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'secure-variable-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SECURE-VARIANT',
            'price' => 25_000,
            'cost_price' => 5_000,
            'barcode' => '123456789',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.variants', 1)
                    ->missing(
                        'product.variants.0.cost_price',
                    )
                    ->missing(
                        'product.variants.0.costPrice',
                    )
                    ->missing(
                        'product.variants.0.barcode',
                    )
                    ->missing(
                        'product.variants.0.weight',
                    ),
            );
    }

    public function test_unknown_product_slug_returns_not_found(): void
    {
        $this->get('/products/does-not-exist')
            ->assertNotFound();
    }
}
