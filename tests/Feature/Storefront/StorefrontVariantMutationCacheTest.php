<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\CreateProductVariantAction;
use App\Domain\Catalog\Actions\DeleteProductVariantAction;
use App\Domain\Catalog\Actions\UpdateProductVariantAction;
use App\Domain\Catalog\Data\CreateProductVariantData;
use App\Domain\Catalog\Data\UpdateProductVariantData;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontVariantMutationCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_creating_variant_invalidates_storefront_catalog_cache(): void
    {
        $product = $this->createVariableProduct();

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        $variant = app(
            CreateProductVariantAction::class,
        )->execute(
            $product,
            $this->createVariantData(),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseHas(
            'product_variants',
            [
                'id' => $variant->id,
                'product_id' => $product->id,
                'sku' => 'CACHE-VARIANT-CREATE-001',
            ],
        );
    }

    public function test_updating_variant_invalidates_storefront_catalog_cache(): void
    {
        $product = $this->createVariableProduct();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'CACHE-VARIANT-UPDATE-001',
            'price' => 12000,
            'compare_at_price' => 15000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        $updatedVariant = app(
            UpdateProductVariantAction::class,
        )->execute(
            $variant,
            $this->updateVariantData(),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertSame(
            13000,
            $updatedVariant->price,
        );

        $this->assertDatabaseHas(
            'product_variants',
            [
                'id' => $variant->id,
                'sku' => 'CACHE-VARIANT-UPDATE-001',
                'price' => 13000,
            ],
        );
    }

    public function test_deleting_variant_invalidates_storefront_catalog_cache(): void
    {
        $product = $this->createVariableProduct();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'CACHE-VARIANT-DELETE-001',
            'price' => 12000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        app(
            DeleteProductVariantAction::class,
        )->execute(
            $variant,
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseMissing(
            'product_variants',
            [
                'id' => $variant->id,
            ],
        );
    }

    private function createVariableProduct(): Product
    {
        return Product::factory()->create([
            'type' => ProductType::Variable,
            'sku' => null,
            'price' => 10000,
            'compare_at_price' => 15000,
        ]);
    }

    private function createVariantData(): CreateProductVariantData
    {
        return new CreateProductVariantData(
            sku: 'CACHE-VARIANT-CREATE-001',
            name: 'Cache Variant',
            price: 12000,
            compareAtPrice: 15000,
            costPrice: 8000,
            barcode: null,
            position: 0,
            isActive: true,
            isDefault: true,
            weight: null,
            attributeValueIds: [],
        );
    }

    private function updateVariantData(): UpdateProductVariantData
    {
        return new UpdateProductVariantData(
            sku: 'CACHE-VARIANT-UPDATE-001',
            name: 'Updated Cache Variant',
            price: 13000,
            compareAtPrice: 16000,
            costPrice: 8500,
            barcode: null,
            position: 0,
            isActive: true,
            isDefault: true,
            weight: null,
            attributeValueIds: [],
        );
    }
}
