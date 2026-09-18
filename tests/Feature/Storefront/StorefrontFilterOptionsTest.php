<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_only_exposes_brands_with_public_products(): void
    {
        $publicBrand = Brand::factory()->create([
            'name' => 'Public Brand',
            'slug' => 'public-brand',
            'is_active' => true,
        ]);

        $draftBrand = Brand::factory()->create([
            'name' => 'Draft Brand',
            'slug' => 'draft-brand',
            'is_active' => true,
        ]);

        Brand::factory()->create([
            'name' => 'Unused Brand',
            'slug' => 'unused-brand',
            'is_active' => true,
        ]);

        $publicProduct = Product::factory()->create([
            'brand_id' => $publicBrand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'brand_id' => $draftBrand->id,
            'status' => ProductStatus::Draft,
        ]);

        $this->assertNotNull($publicProduct);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('filterOptions.brands', 1)
                    ->where(
                        'filterOptions.brands.0.slug',
                        'public-brand',
                    ),
            );
    }

    public function test_shop_only_exposes_active_categories_with_public_products(): void
    {
        $publicCategory = Category::factory()->create([
            'name' => 'Public Category',
            'slug' => 'public-category',
            'is_active' => true,
        ]);

        $emptyCategory = Category::factory()->create([
            'name' => 'Empty Category',
            'slug' => 'empty-category',
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Category',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $product->categories()->attach([
            $publicCategory->id,
            $inactiveCategory->id,
        ]);

        $this->assertNotNull($emptyCategory);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('filterOptions.categories', 1)
                    ->where(
                        'filterOptions.categories.0.slug',
                        'public-category',
                    ),
            );
    }

    public function test_category_page_does_not_return_category_filter_options(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $this->get('/category/electronics')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'filterOptions.categories',
                        0,
                    ),
            );
    }

    public function test_category_brand_options_are_scoped_to_that_category(): void
    {
        $electronics = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $clothing = Category::factory()->create([
            'slug' => 'clothing',
            'is_active' => true,
        ]);

        $electronicsBrand = Brand::factory()->create([
            'slug' => 'electronics-brand',
            'is_active' => true,
        ]);

        $clothingBrand = Brand::factory()->create([
            'slug' => 'clothing-brand',
            'is_active' => true,
        ]);

        $electronicsProduct = Product::factory()->create([
            'brand_id' => $electronicsBrand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $electronicsProduct
            ->categories()
            ->attach($electronics);

        $clothingProduct = Product::factory()->create([
            'brand_id' => $clothingBrand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $clothingProduct
            ->categories()
            ->attach($clothing);

        $this->get('/category/electronics')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('filterOptions.brands', 1)
                    ->where(
                        'filterOptions.brands.0.slug',
                        'electronics-brand',
                    ),
            );
    }

    public function test_attribute_options_only_include_publicly_usable_values(): void
    {
        $attribute = ProductAttribute::factory()->create([
            'name' => 'Color',
            'slug' => 'color',
            'is_active' => true,
        ]);

        $black = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => 'Black',
            'slug' => 'black',
            'is_active' => true,
        ]);

        $unused = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => 'Unused',
            'slug' => 'unused',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->variable()
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PUBLIC-BLACK-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black);

        $this->assertNotNull($unused);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('filterOptions.attributes', 1)
                    ->where(
                        'filterOptions.attributes.0.slug',
                        'color',
                    )
                    ->has(
                        'filterOptions.attributes.0.values',
                        1,
                    )
                    ->where(
                        'filterOptions.attributes.0.values.0.slug',
                        'black',
                    ),
            );
    }

    public function test_inactive_attribute_is_not_exposed(): void
    {
        $attribute = ProductAttribute::factory()->create([
            'name' => 'Hidden Color',
            'slug' => 'hidden-color',
            'is_active' => false,
        ]);

        $black = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => 'Black',
            'slug' => 'black',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->variable()
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'HIDDEN-ATTRIBUTE-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'filterOptions.attributes',
                        0,
                    ),
            );
    }

    public function test_empty_public_catalog_has_null_price_boundaries(): void
    {
        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'filterOptions.min_price',
                        null,
                    )
                    ->where(
                        'filterOptions.max_price',
                        null,
                    ),
            );
    }

    public function test_price_boundaries_use_public_effective_prices(): void
    {
        Product::factory()->create([
            'name' => 'Simple',
            'slug' => 'simple-price-boundary',
            'price' => 5_000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $variable = Product::factory()
            ->variable()
            ->create([
                'name' => 'Variable',
                'slug' => 'variable-price-boundary',
                'price' => 10_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $variable->id,
            'sku' => 'BOUNDARY-001',
            'price' => 30_000,
            'is_active' => true,
            'is_default' => true,
        ]);

        Product::factory()->create([
            'name' => 'Draft Expensive',
            'slug' => 'draft-expensive',
            'price' => 999_999,
            'status' => ProductStatus::Draft,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'filterOptions.min_price',
                        5_000,
                    )
                    ->where(
                        'filterOptions.max_price',
                        30_000,
                    ),
            );
    }
}
