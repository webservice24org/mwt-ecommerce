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

final class StorefrontProductFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_filtered_by_active_brand(): void
    {
        $brandA = Brand::factory()->create([
            'name' => 'Brand A',
            'slug' => 'brand-a',
            'is_active' => true,
        ]);

        $brandB = Brand::factory()->create([
            'name' => 'Brand B',
            'slug' => 'brand-b',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Product A',
            'slug' => 'product-a',
            'brand_id' => $brandA->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Product B',
            'slug' => 'product-b',
            'brand_id' => $brandB->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products?brand=brand-a')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.slug',
                        'product-a',
                    )
                    ->where(
                        'filters.brand',
                        'brand-a',
                    ),
            );
    }

    public function test_inactive_brand_cannot_be_used_to_expose_products(): void
    {
        $brand = Brand::factory()->create([
            'slug' => 'hidden-brand',
            'is_active' => false,
        ]);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products?brand=hidden-brand')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_products_can_be_filtered_by_active_category(): void
    {
        $categoryA = Category::factory()->create([
            'name' => 'Category A',
            'slug' => 'category-a',
            'is_active' => true,
        ]);

        $categoryB = Category::factory()->create([
            'name' => 'Category B',
            'slug' => 'category-b',
            'is_active' => true,
        ]);

        $productA = Product::factory()->create([
            'name' => 'Product A',
            'slug' => 'category-product-a',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $productB = Product::factory()->create([
            'name' => 'Product B',
            'slug' => 'category-product-b',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $productA->categories()->attach($categoryA);
        $productB->categories()->attach($categoryB);

        $this->get('/products?category=category-a')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.slug',
                        'category-product-a',
                    ),
            );
    }

    public function test_inactive_category_filter_does_not_expose_products(): void
    {
        $category = Category::factory()->create([
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $product->categories()->attach($category);

        $this->get('/products?category=hidden-category')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_simple_product_can_be_filtered_by_price_range(): void
    {
        Product::factory()->create([
            'name' => 'Cheap Product',
            'slug' => 'cheap-product',
            'price' => 5_000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Target Product',
            'slug' => 'target-product',
            'price' => 15_000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Expensive Product',
            'slug' => 'expensive-product',
            'price' => 30_000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get(
            '/products?min_price=10000&max_price=20000',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.slug',
                        'target-product',
                    ),
            );
    }

    public function test_variable_product_uses_effective_variant_price_for_filtering(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Variable Product',
                'slug' => 'variable-product',
                'price' => 10_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIABLE-PRICE-001',
            'price' => 25_000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get(
            '/products?min_price=20000&max_price=30000',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.slug',
                        'variable-product',
                    ),
            );
    }

    public function test_variable_product_inherited_price_can_be_filtered(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Inherited Product',
                'slug' => 'inherited-product',
                'price' => 18_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INHERITED-001',
            'price' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get(
            '/products?min_price=17000&max_price=19000',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.slug',
                        'inherited-product',
                    ),
            );
    }

    public function test_inactive_variant_price_does_not_make_product_match(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'inactive-price-product',
                'price' => 5_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INACTIVE-PRICE-001',
            'price' => 25_000,
            'is_active' => false,
            'is_default' => true,
        ]);

        $this->get(
            '/products?min_price=20000&max_price=30000',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0),
            );
    }

    public function test_unknown_brand_returns_empty_listing(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products?brand=does-not-exist')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_unknown_category_returns_empty_listing(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products?category=does-not-exist')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }
}
