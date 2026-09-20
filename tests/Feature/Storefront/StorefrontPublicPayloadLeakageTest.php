<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontPublicPayloadLeakageTest extends TestCase
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

    public function test_complete_product_detail_payload_matches_exact_public_contract(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Public Brand',
            'slug' => 'public-brand',
        ]);

        $category = Category::factory()->create([
            'name' => 'Public Category',
            'slug' => 'public-category',
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->variable()
            ->for($brand)
            ->create([
                'name' => 'Public Product',
                'type' => ProductType::Variable,
                'sku' => null,
                'price' => 15000,
                'compare_at_price' => 20000,
                'cost_price' => 7000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'short_description' => 'Public short description',
                'description' => '<p>Public description</p>',
                'meta_title' => 'Public Meta Title',
                'meta_description' => 'Public meta description',
                'position' => 99,
            ]);

        $product->categories()->attach(
            $category->id,
        );

        $attribute = ProductAttribute::factory()->create([
            'name' => 'Color',
            'slug' => 'color',
            'is_active' => true,
        ]);

        $value = AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'name' => 'Black',
                'slug' => 'black',
                'is_active' => true,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'PUBLIC-VARIANT-SKU',
                'name' => 'Black',
                'price' => 12500,
                'compare_at_price' => 18000,
                'cost_price' => 6000,
                'barcode' => 'PRIVATE-BARCODE-123',
                'weight' => 1.250,
                'position' => 88,
                'is_active' => true,
                'is_default' => true,
            ]);

        $variant->attributeValues()->attach(
            $value->id,
        );

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/audit/images/public.jpg',
                'original_name' => 'PRIVATE-ORIGINAL-IMAGE.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 987654,
                'width' => 1600,
                'height' => 1200,
                'alt_text' => 'Public image',
                'position' => 77,
            ]);

        ProductVideo::factory()
            ->for($product)
            ->uploaded()
            ->create([
                'path' => 'catalog/products/audit/videos/public.mp4',
                'url' => null,
                'original_name' => 'PRIVATE-ORIGINAL-VIDEO.mp4',
                'mime_type' => 'video/mp4',
                'file_size' => 1234567,
                'title' => 'PRIVATE INTERNAL VIDEO TITLE',
            ]);

        $payload = $this->publicPayload(
            $product,
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
                'type',
                'sku',
                'short_description',
                'description',
                'pricing',
                'brand',
                'categories',
                'images',
                'variants',
                'meta_title',
                'meta_description',
                'video',
            ],
            array_keys($payload),
        );

        $this->assertSame(
            [
                'price',
                'compare_at_price',
                'on_sale',
            ],
            array_keys($payload['pricing']),
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
            ],
            array_keys($payload['brand']),
        );

        $this->assertCount(
            1,
            $payload['categories'],
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
            ],
            array_keys($payload['categories'][0]),
        );

        $this->assertCount(
            1,
            $payload['images'],
        );

        $this->assertSame(
            [
                'id',
                'url',
                'alt',
                'width',
                'height',
            ],
            array_keys($payload['images'][0]),
        );

        $this->assertCount(
            1,
            $payload['variants'],
        );

        $this->assertSame(
            [
                'id',
                'sku',
                'name',
                'is_default',
                'pricing',
                'attribute_values',
            ],
            array_keys($payload['variants'][0]),
        );

        $this->assertSame(
            [
                'price',
                'compare_at_price',
                'on_sale',
            ],
            array_keys(
                $payload['variants'][0]['pricing'],
            ),
        );

        $this->assertCount(
            1,
            $payload['variants'][0]['attribute_values'],
        );

        $attributeValuePayload =
            $payload['variants'][0]['attribute_values'][0];

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
                'attribute',
            ],
            array_keys($attributeValuePayload),
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
            ],
            array_keys(
                $attributeValuePayload['attribute'],
            ),
        );

        $this->assertSame(
            [
                'type',
                'url',
            ],
            array_keys($payload['video']),
        );
    }

    public function test_public_payload_does_not_leak_sensitive_or_internal_database_fields(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Leak Audit Brand',
            'slug' => 'leak-audit-brand',
        ]);

        $category = Category::factory()->create([
            'name' => 'Leak Audit Category',
            'slug' => 'leak-audit-category',
            'is_active' => true,
            'position' => 81,
        ]);

        $product = Product::factory()
            ->variable()
            ->for($brand)
            ->create([
                'name' => 'Leak Audit Product',
                'price' => 15000,
                'compare_at_price' => 20000,
                'cost_price' => 7654321,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'position' => 91,
            ]);

        $product->categories()->attach(
            $category->id,
        );

        $attribute = ProductAttribute::factory()->create([
            'name' => 'Audit Attribute',
            'slug' => 'audit-attribute',
            'position' => 71,
            'is_active' => true,
        ]);

        $value = AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'name' => 'Audit Value',
                'slug' => 'audit-value',
                'position' => 72,
                'is_active' => true,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'PUBLIC-AUDIT-SKU',
                'price' => 13000,
                'compare_at_price' => 18000,
                'cost_price' => 6543210,
                'barcode' => 'PRIVATE-AUDIT-BARCODE',
                'weight' => 9.875,
                'position' => 73,
                'is_active' => true,
                'is_default' => true,
            ]);

        $variant->attributeValues()->attach(
            $value->id,
        );

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/audit/images/leak.jpg',
                'original_name' => 'PRIVATE-IMAGE-FILENAME.jpg',
                'mime_type' => 'image/private-audit',
                'file_size' => 87654321,
                'position' => 74,
            ]);

        ProductVideo::factory()
            ->for($product)
            ->uploaded()
            ->create([
                'path' => 'catalog/products/audit/videos/leak.mp4',
                'original_name' => 'PRIVATE-VIDEO-FILENAME.mp4',
                'mime_type' => 'video/private-audit',
                'file_size' => 76543210,
                'title' => 'PRIVATE VIDEO DATABASE TITLE',
            ]);

        $payload = $this->publicPayload(
            $product,
        );

        $this->assertForbiddenKeysRecursively(
            $payload,
            [
                'cost_price',
                'barcode',
                'weight',
                'position',
                'is_active',
                'is_primary',
                'path',
                'original_name',
                'mime_type',
                'file_size',
                'product_id',
                'brand_id',
                'parent_id',
                'attribute_id',
                'variant_id',
                'created_at',
                'updated_at',
                'published_at',
                'deleted_at',
            ],
        );

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-AUDIT-BARCODE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-IMAGE-FILENAME.jpg',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-VIDEO-FILENAME.mp4',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE VIDEO DATABASE TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'image/private-audit',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'video/private-audit',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '7654321',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '6543210',
            $encoded,
        );
    }

    public function test_attribute_value_serialization_does_not_leak_internal_constructor_field_names(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 15000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $attribute = ProductAttribute::factory()->create([
            'name' => 'Size',
            'slug' => 'size',
            'is_active' => true,
        ]);

        $value = AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'name' => 'Large',
                'slug' => 'large',
                'is_active' => true,
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        $variant->attributeValues()->attach(
            $value->id,
        );

        $payload = $this->publicPayload(
            $product,
        );

        $valuePayload =
            $payload['variants'][0]['attribute_values'][0];

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
                'attribute',
            ],
            array_keys($valuePayload),
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
            ],
            array_keys(
                $valuePayload['attribute'],
            ),
        );

        $this->assertArrayNotHasKey(
            'attributeId',
            $valuePayload,
        );

        $this->assertArrayNotHasKey(
            'attributeName',
            $valuePayload,
        );

        $this->assertArrayNotHasKey(
            'attributeSlug',
            $valuePayload,
        );

        $this->assertArrayNotHasKey(
            'attribute_id',
            $valuePayload,
        );
    }

    public function test_effective_pricing_does_not_leak_cost_or_inheritance_internals(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 15000,
                'compare_at_price' => 20000,
                'cost_price' => 8000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()
            ->for($product)
            ->create([
                'price' => null,
                'compare_at_price' => null,
                'cost_price' => 7000,
                'is_active' => true,
                'is_default' => true,
            ]);

        $payload = $this->publicPayload(
            $product,
        );

        $this->assertSame(
            [
                'price',
                'compare_at_price',
                'on_sale',
            ],
            array_keys($payload['pricing']),
        );

        $variantPricing =
            $payload['variants'][0]['pricing'];

        $this->assertSame(
            [
                'price',
                'compare_at_price',
                'on_sale',
            ],
            array_keys($variantPricing),
        );

        $this->assertSame(
            15000,
            $variantPricing['price'],
        );

        $this->assertSame(
            20000,
            $variantPricing['compare_at_price'],
        );

        $this->assertTrue(
            $variantPricing['on_sale'],
        );

        $this->assertArrayNotHasKey(
            'cost_price',
            $variantPricing,
        );

        $this->assertArrayNotHasKey(
            'price_inherited',
            $variantPricing,
        );

        $this->assertArrayNotHasKey(
            'compare_at_price_inherited',
            $variantPricing,
        );

        $this->assertArrayNotHasKey(
            'cost_price_inherited',
            $variantPricing,
        );
    }

    public function test_null_optional_relations_keep_same_public_contract(): void
    {
        $product = Product::factory()->create([
            'brand_id' => null,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $payload = $this->publicPayload(
            $product,
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
                'type',
                'sku',
                'short_description',
                'description',
                'pricing',
                'brand',
                'categories',
                'images',
                'variants',
                'meta_title',
                'meta_description',
                'video',
            ],
            array_keys($payload),
        );

        $this->assertNull(
            $payload['brand'],
        );

        $this->assertNull(
            $payload['video'],
        );

        $this->assertSame(
            [],
            $payload['categories'],
        );

        $this->assertSame(
            [],
            $payload['images'],
        );

        $this->assertSame(
            [],
            $payload['variants'],
        );
    }

    public function test_cached_payload_has_identical_public_shape_and_no_internal_fields(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 15000,
                'cost_price' => 8765432,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'CACHE-PUBLIC-SKU',
                'price' => 14000,
                'cost_price' => 7654321,
                'barcode' => 'CACHE-PRIVATE-BARCODE',
                'is_active' => true,
                'is_default' => true,
            ]);

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'original_name' => 'CACHE-PRIVATE-IMAGE.jpg',
                'mime_type' => 'image/private-cache',
                'file_size' => 555555,
            ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $cache->invalidate();

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $cold = $query->findBySlug(
            $product->slug,
        );

        $warm = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($cold);
        $this->assertNotNull($warm);

        $coldPayload = $cold->toArray();
        $warmPayload = $warm->toArray();

        $this->assertSame(
            $coldPayload,
            $warmPayload,
        );

        $this->assertForbiddenKeysRecursively(
            $warmPayload,
            [
                'cost_price',
                'barcode',
                'weight',
                'position',
                'is_active',
                'is_primary',
                'path',
                'original_name',
                'mime_type',
                'file_size',
                'product_id',
                'brand_id',
                'attribute_id',
                'created_at',
                'updated_at',
                'published_at',
            ],
        );

        $encoded = json_encode(
            $warmPayload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'CACHE-PRIVATE-BARCODE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'CACHE-PRIVATE-IMAGE.jpg',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'image/private-cache',
            $encoded,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function publicPayload(
        Product $product,
    ): array {
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $detail = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNotNull(
            $detail,
        );

        return $detail->toArray();
    }

    /**
     * @param  array<mixed>  $payload
     * @param  list<string>  $forbiddenKeys
     */
    private function assertForbiddenKeysRecursively(
        array $payload,
        array $forbiddenKeys,
    ): void {
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $this->assertNotContains(
                    $key,
                    $forbiddenKeys,
                    sprintf(
                        'Forbidden public payload key [%s] was exposed.',
                        $key,
                    ),
                );
            }

            if (is_array($value)) {
                $this->assertForbiddenKeysRecursively(
                    $value,
                    $forbiddenKeys,
                );
            }
        }
    }
}
