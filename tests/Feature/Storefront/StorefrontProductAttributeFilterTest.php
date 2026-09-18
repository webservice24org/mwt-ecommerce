<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductAttributeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_without_attribute_filters_is_unaffected(): void
    {
        $product = $this->createPublishedVariableProduct(
            name: 'Test Shirt',
            slug: 'test-shirt',
        );

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'TEST-SHIRT-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Test Shirt',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_product_with_active_variant_matching_attribute_is_included(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Black Shirt',
            slug: 'black-shirt',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BLACK-SHIRT-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Black Shirt',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_same_active_variant_must_match_all_selected_attributes(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $size = $this->createAttribute(
            name: 'Size',
            slug: 'size',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $large = $this->createAttributeValue(
            attribute: $size,
            name: 'Large',
            slug: 'large',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Black Large Shirt',
            slug: 'black-large-shirt',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BLACK-LARGE-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach([
            $black->id,
            $large->id,
        ]);

        $this->get(
            '/products?attributes[color]=black&attributes[size]=large',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Black Large Shirt',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_matches_across_different_variants_do_not_satisfy_combined_filter(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $size = $this->createAttribute(
            name: 'Size',
            slug: 'size',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $white = $this->createAttributeValue(
            attribute: $color,
            name: 'White',
            slug: 'white',
        );

        $small = $this->createAttributeValue(
            attribute: $size,
            name: 'Small',
            slug: 'small',
        );

        $large = $this->createAttributeValue(
            attribute: $size,
            name: 'Large',
            slug: 'large',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Mixed Shirt',
            slug: 'mixed-shirt',
        );

        $blackSmall = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'MIXED-BLACK-SMALL',
            'is_active' => true,
            'is_default' => true,
        ]);

        $blackSmall->attributeValues()->attach([
            $black->id,
            $small->id,
        ]);

        $whiteLarge = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'MIXED-WHITE-LARGE',
            'is_active' => true,
            'is_default' => false,
        ]);

        $whiteLarge->attributeValues()->attach([
            $white->id,
            $large->id,
        ]);

        $this->get(
            '/products?attributes[color]=black&attributes[size]=large',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_matching_inactive_variant_is_excluded(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Inactive Black Shirt',
            slug: 'inactive-black-shirt',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INACTIVE-BLACK-001',
            'is_active' => false,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_value_belonging_to_inactive_attribute_is_excluded(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            isActive: false,
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Hidden Color Shirt',
            slug: 'hidden-color-shirt',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'HIDDEN-COLOR-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_inactive_attribute_value_is_excluded(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
            isActive: false,
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Hidden Black Shirt',
            slug: 'hidden-black-shirt',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'HIDDEN-BLACK-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_unknown_attribute_slug_returns_empty_listing(): void
    {
        $this->createPublishedVariableProduct(
            name: 'Test Product',
            slug: 'test-product',
        );

        $this->get(
            '/products?attributes[unknown]=something',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_unknown_attribute_value_slug_returns_empty_listing(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = $this->createPublishedVariableProduct(
            name: 'Black Product',
            slug: 'black-product',
        );

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'BLACK-PRODUCT-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=purple',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_matching_draft_product_is_not_exposed(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Draft Black Product',
                'slug' => 'draft-black-product',
                'status' => ProductStatus::Draft,
                'published_at' => null,
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'DRAFT-BLACK-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_matching_future_product_is_not_exposed(): void
    {
        $color = $this->createAttribute(
            name: 'Color',
            slug: 'color',
        );

        $black = $this->createAttributeValue(
            attribute: $color,
            name: 'Black',
            slug: 'black',
        );

        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Future Black Product',
                'slug' => 'future-black-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->addDay(),
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'FUTURE-BLACK-001',
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant->attributeValues()->attach($black->id);

        $this->get(
            '/products?attributes[color]=black',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    private function createPublishedVariableProduct(
        string $name,
        string $slug,
    ): Product {
        return Product::factory()
            ->variable()
            ->create([
                'name' => $name,
                'slug' => $slug,
                'type' => ProductType::Variable,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);
    }

    private function createAttribute(
        string $name,
        string $slug,
        bool $isActive = true,
    ): ProductAttribute {
        return ProductAttribute::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
    }

    private function createAttributeValue(
        ProductAttribute $attribute,
        string $name,
        string $slug,
        bool $isActive = true,
    ): AttributeValue {
        return AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
    }
}
