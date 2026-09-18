<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_exposes_only_public_card_data(): void
    {
        $product = Product::factory()->create([
            'name' => 'Public Product',
            'slug' => 'public-product',
            'type' => ProductType::Simple,
            'sku' => 'PUBLIC-001',
            'price' => 15000,
            'compare_at_price' => 20000,
            'cost_price' => 9000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/public.webp',
            'original_name' => 'internal-original.webp',
            'mime_type' => 'image/webp',
            'file_size' => 123456,
            'width' => 800,
            'height' => 800,
            'alt_text' => 'Public product',
            'position' => 0,
            'is_primary' => true,
        ]);

        $response = $this->get('/products');

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Products/Index')
                    ->where('products.data.0.name', 'Public Product')
                    ->where(
                        'products.data.0.pricing.min_price',
                        15000,
                    )
                    ->where(
                        'products.data.0.pricing.max_price',
                        15000,
                    )
                    ->where(
                        'products.data.0.pricing.compare_at_price',
                        20000,
                    )
                    ->where(
                        'products.data.0.pricing.on_sale',
                        true,
                    )
                    ->where(
                        'products.data.0.pricing.varies',
                        false,
                    )
                    ->where(
                        'products.data.0.image.alt',
                        'Public product',
                    )
                    ->missing('products.data.0.cost_price')
                    ->missing('products.data.0.sku')
                    ->missing('products.data.0.image.original_name')
                    ->missing('products.data.0.image.mime_type')
                    ->missing('products.data.0.image.file_size'),
            );
    }

    public function test_product_detail_does_not_expose_sensitive_commercial_data(): void
    {
        $product = Product::factory()->create([
            'name' => 'Variable Product',
            'slug' => 'variable-product',
            'type' => ProductType::Variable,
            'sku' => null,
            'price' => 10000,
            'compare_at_price' => 15000,
            'cost_price' => 7000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-001',
            'name' => 'Large',
            'price' => 12000,
            'compare_at_price' => null,
            'cost_price' => 8000,
            'barcode' => 'SECRET-BARCODE',
            'is_active' => true,
            'is_default' => true,
            'position' => 0,
        ]);

        $response = $this->get(
            "/products/{$product->slug}",
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Products/Show')
                    ->where('product.name', 'Variable Product')
                    ->where(
                        'product.variants.0.sku',
                        'VAR-001',
                    )
                    ->where(
                        'product.variants.0.pricing.price',
                        12000,
                    )
                    ->where(
                        'product.variants.0.pricing.compare_at_price',
                        15000,
                    )
                    ->where(
                        'product.variants.0.pricing.on_sale',
                        true,
                    )
                    ->missing('product.cost_price')
                    ->missing('product.variants.0.cost_price')
                    ->missing('product.variants.0.barcode')
                    ->missing('product.variants.0.price')
                    ->missing(
                        'product.variants.0.compare_at_price',
                    ),
            );
    }

    public function test_variant_effective_pricing_inherits_product_price(): void
    {
        $product = Product::factory()->create([
            'slug' => 'inherited-price-product',
            'type' => ProductType::Variable,
            'price' => 25000,
            'compare_at_price' => 30000,
            'cost_price' => 17000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INHERIT-001',
            'price' => null,
            'compare_at_price' => null,
            'cost_price' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get(
            "/products/{$product->slug}",
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.variants.0.pricing.price',
                        25000,
                    )
                    ->where(
                        'product.variants.0.pricing.compare_at_price',
                        30000,
                    )
                    ->where(
                        'product.variants.0.pricing.on_sale',
                        true,
                    ),
            );
    }

    public function test_inactive_variants_are_not_exposed(): void
    {
        $product = Product::factory()->create([
            'slug' => 'variant-visibility-product',
            'type' => ProductType::Variable,
            'price' => 10000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'ACTIVE-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INACTIVE-001',
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->get(
            "/products/{$product->slug}",
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.variants', 1)
                    ->where(
                        'product.variants.0.sku',
                        'ACTIVE-001',
                    ),
            );
    }

    public function test_inactive_attribute_values_are_not_exposed(): void
    {
        $product = Product::factory()->create([
            'slug' => 'attribute-visibility-product',
            'type' => ProductType::Variable,
            'price' => 10000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $attribute = ProductAttribute::factory()->create([
            'name' => 'Color',
            'slug' => 'color',
            'is_active' => true,
        ]);

        $activeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => 'Red',
            'slug' => 'red',
            'is_active' => true,
        ]);

        $inactiveValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => 'Blue',
            'slug' => 'blue',
            'is_active' => false,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'COLOR-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant
            ->attributeValues()
            ->attach([
                $activeValue->id,
                $inactiveValue->id,
            ]);

        $this->get(
            "/products/{$product->slug}",
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'product.variants.0.attribute_values',
                        1,
                    )
                    ->where(
                        'product.variants.0.attribute_values.0.slug',
                        fn ($value) => $value === 'red',
                    )
                    ->where(
                        'product.variants.0.attribute_values.0.attribute.slug',
                        fn ($value) => $value === 'color',
                    ),
            );
    }

    public function test_unpublished_products_are_not_present_in_index(): void
    {
        Product::factory()->create([
            'name' => 'Published Product',
            'slug' => 'published-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Draft Product',
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
        ]);

        Product::factory()->create([
            'name' => 'Future Product',
            'slug' => 'future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Published Product',
                    ),
            );
    }

    public function test_unpublished_product_detail_returns_not_found(): void
    {
        $draft = Product::factory()->create([
            'slug' => 'private-draft',
            'status' => ProductStatus::Draft,
        ]);

        $future = Product::factory()->create([
            'slug' => 'future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get(
            "/products/{$draft->slug}",
        )->assertNotFound();

        $this->get(
            "/products/{$future->slug}",
        )->assertNotFound();
    }

    public function test_variable_product_card_exposes_effective_price_range(): void
    {
        $product = Product::factory()->create([
            'name' => 'Variable Price Product',
            'slug' => 'variable-price-product',
            'type' => ProductType::Variable,
            'price' => 10000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PRICE-SMALL',
            'price' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PRICE-LARGE',
            'price' => 15000,
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.pricing.min_price',
                        10000,
                    )
                    ->where(
                        'products.data.0.pricing.max_price',
                        15000,
                    )
                    ->where(
                        'products.data.0.pricing.varies',
                        true,
                    ),
            );
    }

    public function test_inactive_variant_does_not_affect_listing_price_range(): void
    {
        $product = Product::factory()->create([
            'name' => 'Range Product',
            'slug' => 'range-product',
            'type' => ProductType::Variable,
            'price' => 10000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'RANGE-ACTIVE',
            'price' => 12000,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'RANGE-INACTIVE',
            'price' => 999999,
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.pricing.min_price',
                        12000,
                    )
                    ->where(
                        'products.data.0.pricing.max_price',
                        12000,
                    )
                    ->where(
                        'products.data.0.pricing.varies',
                        false,
                    ),
            );
    }
}
