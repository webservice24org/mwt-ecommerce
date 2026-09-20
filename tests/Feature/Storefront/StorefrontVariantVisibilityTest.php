<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontVariantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_active_variant_is_exposed_in_public_product_detail(): void
    {
        $product = $this->createVariableProduct();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 12500,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertSame(
            $variant->id,
            $detail->variants[0]->id,
        );

        $this->assertSame(
            $variant->sku,
            $detail->variants[0]->sku,
        );
    }

    public function test_inactive_variant_is_not_exposed_in_public_product_detail(): void
    {
        $product = $this->createVariableProduct();

        $active = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 12500,
            ]);

        $inactive = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => false,
                'is_default' => false,
                'price' => 22500,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertSame(
            $active->id,
            $detail->variants[0]->id,
        );

        $this->assertFalse(
            collect($detail->variants)
                ->contains(
                    static fn ($variant): bool => $variant->id === $inactive->id,
                ),
        );
    }

    public function test_variable_product_with_only_inactive_variants_remains_public_but_exposes_no_variants(): void
    {
        $product = $this->createVariableProduct();

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => false,
                'is_default' => true,
                'price' => 12500,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull(
            $detail,
        );

        $this->assertSame(
            $product->id,
            $detail->id,
        );

        $this->assertSame(
            [],
            $detail->variants,
        );
    }

    public function test_variant_with_null_price_inherits_product_base_price(): void
    {
        $product = $this->createVariableProduct(
            price: 15000,
            compareAtPrice: 20000,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => null,
                'compare_at_price' => null,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $pricing = $detail->variants[0]->pricing;

        $this->assertSame(
            15000,
            $pricing->price,
        );

        $this->assertSame(
            20000,
            $pricing->compareAtPrice,
        );

        $this->assertTrue(
            $pricing->onSale,
        );
    }

    public function test_explicit_variant_price_overrides_product_base_price(): void
    {
        $product = $this->createVariableProduct(
            price: 15000,
            compareAtPrice: 20000,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 12000,
                'compare_at_price' => 18000,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $pricing = $detail->variants[0]->pricing;

        $this->assertSame(
            12000,
            $pricing->price,
        );

        $this->assertSame(
            18000,
            $pricing->compareAtPrice,
        );

        $this->assertTrue(
            $pricing->onSale,
        );
    }

    public function test_explicit_zero_variant_price_is_not_treated_as_inherited_price(): void
    {
        $product = $this->createVariableProduct(
            price: 15000,
            compareAtPrice: null,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 0,
                'compare_at_price' => null,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertSame(
            0,
            $detail->variants[0]->pricing->price,
        );
    }

    public function test_inactive_variant_cannot_leak_sku_or_attribute_values(): void
    {
        $product = $this->createVariableProduct();

        $active = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'PUBLIC-SKU',
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        $inactive = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'PRIVATE-INACTIVE-SKU',
                'is_active' => false,
                'is_default' => false,
                'price' => 25000,
            ]);

        $activeValue = $this->createAttributeValue(
            name: 'Black',
            slug: 'black',
        );

        $inactiveValue = $this->createAttributeValue(
            name: 'Secret Red',
            slug: 'secret-red',
        );

        $active->attributeValues()->attach(
            $activeValue->id,
        );

        $inactive->attributeValues()->attach(
            $inactiveValue->id,
        );

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $payload = $detail->toArray();

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString(
            'PUBLIC-SKU',
            $encoded,
        );

        $this->assertStringContainsString(
            'black',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-INACTIVE-SKU',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'secret-red',
            $encoded,
        );
    }

    public function test_public_variant_payload_does_not_expose_internal_variant_fields(): void
    {
        $product = $this->createVariableProduct();

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
                'cost_price' => 7000,
                'barcode' => 'PRIVATE-BARCODE-123',
                'weight' => 1.250,
                'position' => 47,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $payload = $detail->variants[0]->toArray();

        $this->assertSame(
            [
                'id',
                'sku',
                'name',
                'is_default',
                'pricing',
                'attribute_values',
            ],
            array_keys($payload),
        );

        $this->assertArrayNotHasKey(
            'cost_price',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'barcode',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'weight',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'is_active',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'position',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'price',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'compare_at_price',
            $payload,
        );

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-BARCODE-123',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'cost_price',
            $encoded,
        );
    }

    public function test_draft_product_does_not_expose_active_variants_through_public_detail_query(): void
    {
        $product = $this->createVariableProduct(
            status: ProductStatus::Draft,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'DRAFT-VARIANT-SKU',
                'is_active' => true,
                'is_default' => true,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNull(
            $detail,
        );
    }

    public function test_future_product_does_not_expose_active_variants_through_public_detail_query(): void
    {
        $product = $this->createVariableProduct(
            status: ProductStatus::Published,
            publishedAt: now()->addHour(),
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'FUTURE-VARIANT-SKU',
                'is_active' => true,
                'is_default' => true,
            ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNull(
            $detail,
        );
    }

    public function test_cached_product_detail_preserves_variant_visibility_rules(): void
    {
        $product = $this->createVariableProduct();

        $active = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'CACHE-PUBLIC-SKU',
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        $inactive = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'CACHE-PRIVATE-SKU',
                'is_active' => false,
                'is_default' => false,
                'price' => 25000,
            ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $first = $query->findBySlug(
            $product->slug,
        );

        $second = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertCount(
            1,
            $first->variants,
        );

        $this->assertCount(
            1,
            $second->variants,
        );

        $this->assertSame(
            $active->id,
            $second->variants[0]->id,
        );

        $this->assertFalse(
            collect($second->variants)
                ->contains(
                    static fn ($variant): bool => $variant->id === $inactive->id,
                ),
        );

        $encoded = json_encode(
            $second->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString(
            'CACHE-PUBLIC-SKU',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'CACHE-PRIVATE-SKU',
            $encoded,
        );
    }

    private function createVariableProduct(
        int $price = 15000,
        ?int $compareAtPrice = null,
        ProductStatus $status = ProductStatus::Published,
        mixed $publishedAt = null,
    ): Product {
        return Product::factory()
            ->variable()
            ->create([
                'type' => ProductType::Variable,
                'price' => $price,
                'compare_at_price' => $compareAtPrice,
                'status' => $status,
                'published_at' => $publishedAt ?? now()->subMinute(),
            ]);
    }

    private function createAttributeValue(
        string $name,
        string $slug,
    ): AttributeValue {
        $attribute = ProductAttribute::factory()->create([
            'is_active' => true,
        ]);

        return AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
            ]);
    }

    private function freshDetail(
        Product $product,
    ): mixed {
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        return app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );
    }
}
