<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontFilterOptionsData;
use App\Domain\Catalog\Data\Storefront\StorefrontHomeData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontCatalogCacheSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_product_detail_cache_round_trip_preserves_public_dto(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cached Product',
            'slug' => 'cached-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'cost_price' => 7000,
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $first = $query->findBySlug(
            $product->slug,
        );

        $this->assertInstanceOf(
            StorefrontProductDetailData::class,
            $first,
        );

        Product::query()
            ->whereKey($product->id)
            ->update([
                'name' => 'Changed in database',
            ]);

        $second = $query->findBySlug(
            $product->slug,
        );

        $this->assertInstanceOf(
            StorefrontProductDetailData::class,
            $second,
        );

        $this->assertSame(
            'Cached Product',
            $second->name,
        );

        $this->assertSame(
            $first->toArray(),
            $second->toArray(),
        );
    }

    public function test_product_detail_public_payload_does_not_expose_sensitive_commercial_fields(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'secure-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'cost_price' => 5500,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SECURE-VARIANT',
            'cost_price' => 4000,
            'barcode' => 'SECRET-BARCODE-123',
            'is_active' => true,
            'is_default' => true,
        ]);

        $result = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($result);

        $payload = $result->toArray();

        $this->assertArrayNotHasKey(
            'cost_price',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'barcode',
            $payload,
        );

        foreach ($payload['variants'] as $variant) {
            $this->assertArrayNotHasKey(
                'cost_price',
                $variant,
            );

            $this->assertArrayNotHasKey(
                'barcode',
                $variant,
            );

            $this->assertArrayNotHasKey(
                'weight',
                $variant,
            );

            $this->assertArrayNotHasKey(
                'is_active',
                $variant,
            );

            $this->assertArrayNotHasKey(
                'position',
                $variant,
            );
        }
    }

    public function test_inactive_variant_is_not_present_in_cached_product_detail(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'variant-security-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PUBLIC-VARIANT',
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'PRIVATE-VARIANT',
            'is_active' => false,
            'is_default' => false,
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $query->findBySlug(
            $product->slug,
        );

        $cached = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($cached);

        $skus = collect($cached->variants)
            ->pluck('sku')
            ->all();

        $this->assertContains(
            'PUBLIC-VARIANT',
            $skus,
        );

        $this->assertNotContains(
            'PRIVATE-VARIANT',
            $skus,
        );
    }

    public function test_inactive_attribute_value_is_not_present_in_cached_product_detail(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'slug' => 'attribute-security-product',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $attribute = ProductAttribute::factory()->create([
            'is_active' => true,
        ]);

        $activeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'slug' => 'public-value',
            'is_active' => true,
        ]);

        $inactiveValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'slug' => 'private-value',
            'is_active' => false,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant
            ->attributeValues()
            ->sync([
                $activeValue->id,
                $inactiveValue->id,
            ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $query->findBySlug(
            $product->slug,
        );

        $cached = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($cached);

        $slugs = collect($cached->variants)
            ->flatMap(
                static fn ($variant) => $variant->attributeValues,
            )
            ->pluck('slug')
            ->all();

        $this->assertContains(
            'public-value',
            $slugs,
        );

        $this->assertNotContains(
            'private-value',
            $slugs,
        );
    }

    public function test_home_cache_round_trip_preserves_public_dto(): void
    {
        $product = Product::factory()->create([
            'name' => 'Homepage Product',
            'slug' => 'homepage-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'is_featured' => true,
            'cost_price' => 8000,
        ]);

        $query = app(StorefrontHomeQuery::class);

        $first = $query->get();

        $this->assertInstanceOf(
            StorefrontHomeData::class,
            $first,
        );

        Product::query()
            ->whereKey($product->id)
            ->update([
                'name' => 'Changed in database',
            ]);

        $second = $query->get();

        $this->assertInstanceOf(
            StorefrontHomeData::class,
            $second,
        );

        $this->assertSame(
            $first->toArray(),
            $second->toArray(),
        );

        $payload = $second->toArray();

        foreach (
            [
                ...$payload['featured_products'],
                ...$payload['new_arrivals'],
            ] as $card
        ) {
            $this->assertArrayNotHasKey(
                'cost_price',
                $card,
            );

            $this->assertArrayNotHasKey(
                'barcode',
                $card,
            );

            $this->assertArrayNotHasKey(
                'variants',
                $card,
            );
        }
    }

    public function test_filter_options_cache_round_trip_preserves_public_dto(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Public Brand',
            'slug' => 'public-brand',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $query = app(
            StorefrontFilterOptionsQuery::class,
        );

        $first = $query->get();

        $this->assertInstanceOf(
            StorefrontFilterOptionsData::class,
            $first,
        );

        Brand::query()
            ->whereKey($brand->id)
            ->update([
                'name' => 'Changed in database',
            ]);

        $second = $query->get();

        $this->assertInstanceOf(
            StorefrontFilterOptionsData::class,
            $second,
        );

        $this->assertSame(
            $first->toArray(),
            $second->toArray(),
        );

        $payload = $second->toArray();

        $this->assertSame(
            [
                'brands',
                'categories',
                'attributes',
                'min_price',
                'max_price',
            ],
            array_keys($payload),
        );
    }

    public function test_category_cache_round_trip_keeps_cached_metadata_server_side(): void
    {
        $category = Category::factory()->create([
            'name' => 'Cached Category',
            'slug' => 'cached-category',
            'is_active' => true,
        ]);

        $query = app(
            StorefrontCategoryQuery::class,
        );

        $first = $query->findBySlugOrFail(
            $category->slug,
        );

        Category::query()
            ->whereKey($category->id)
            ->update([
                'name' => 'Changed in database',
            ]);

        $second = $query->findBySlugOrFail(
            $category->slug,
        );

        $this->assertSame(
            'Cached Category',
            $first->name,
        );

        $this->assertSame(
            'Cached Category',
            $second->name,
        );

        $this->assertSame(
            $first->id,
            $second->id,
        );
    }
}
