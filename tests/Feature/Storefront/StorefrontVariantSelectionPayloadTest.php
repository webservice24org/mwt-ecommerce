<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontVariantSelectionPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_variable_product_exposes_active_variant_selection_data(): void
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

        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Variable Product',
                'slug' => 'variant-selection-product',
                'price' => 10_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BLACK-001',
            'price' => 15_000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.variants', 1)
                    ->where(
                        'product.variants.0.id',
                        $variant->id,
                    )
                    ->where(
                        'product.variants.0.sku',
                        'BLACK-001',
                    )
                    ->where(
                        'product.variants.0.is_default',
                        true,
                    )
                    ->where(
                        'product.variants.0.pricing.price',
                        15_000,
                    )
                    ->has(
                        'product.variants.0.attribute_values',
                        1,
                    )
                    ->where(
                        'product.variants.0.attribute_values.0.slug',
                        'black',
                    )
                    ->where(
                        'product.variants.0.attribute_values.0.attribute.slug',
                        'color',
                    ),
            );
    }

    public function test_variant_inherited_price_is_resolved_server_side(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'inherited-variant-price',
                'price' => 18_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INHERITED-PRICE-001',
            'price' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.variants.0.pricing.price',
                        18_000,
                    ),
            );
    }

    public function test_inactive_variant_is_not_available_to_storefront_selector(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'inactive-variant-product',
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

        $this->get('/products/'.$product->slug)
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

    public function test_variant_payload_does_not_expose_internal_fields(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'private-variant-fields',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PUBLIC-SKU',
            'price' => 20_000,
            'cost_price' => 5_000,
            'barcode' => '123456789',
            'weight' => 1.250,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
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
                    )
                    ->missing(
                        'product.variants.0.is_active',
                    )
                    ->missing(
                        'product.variants.0.position',
                    ),
            );
    }
}
