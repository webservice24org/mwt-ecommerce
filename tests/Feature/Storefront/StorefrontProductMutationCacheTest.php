<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\CreateProductAction;
use App\Domain\Catalog\Actions\DeleteProductAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Data\CreateProductData;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class StorefrontProductMutationCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_creating_product_invalidates_storefront_catalog_cache(): void
    {
        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        app(
            CreateProductAction::class,
        )->execute(
            $this->createProductData(),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );
    }

    public function test_updating_product_invalidates_storefront_catalog_cache(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => 'CACHE-UPDATE-001',
            'price' => 10000,
            'compare_at_price' => 12000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        app(
            UpdateProductAction::class,
        )->execute(
            $product,
            $this->updateProductData(),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );
    }

    public function test_deleting_product_invalidates_storefront_catalog_cache(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => 'CACHE-DELETE-001',
            'price' => 10000,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $before = $cache->version();

        app(
            DeleteProductAction::class,
        )->execute(
            $product,
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseMissing(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }

    private function createProductData(): CreateProductData
    {
        return new CreateProductData(
            brandId: null,
            type: ProductType::Simple,
            sku: 'CACHE-CREATE-001',
            price: 10000,
            compareAtPrice: 12000,
            costPrice: 8000,
            name: 'Cache Create Product',
            slug: 'cache-create-product',
            shortDescription: 'Storefront cache invalidation test product.',
            description: '<p>Storefront cache invalidation test product.</p>',
            status: ProductStatus::Published,
            isFeatured: true,
            position: 0,
            publishedAt: CarbonImmutable::now()->subMinute(),
            metaTitle: 'Cache Create Product',
            metaDescription: 'Storefront cache invalidation test product.',
            categoryIds: [],
        );
    }

    private function updateProductData(): UpdateProductData
    {
        return new UpdateProductData(
            brandId: null,
            type: ProductType::Simple,
            sku: 'CACHE-UPDATE-001',
            price: 11000,
            compareAtPrice: 13000,
            costPrice: 8500,
            name: 'Updated Cache Product',
            slug: 'updated-cache-product',
            shortDescription: 'Updated storefront cache test product.',
            description: '<p>Updated storefront cache test product.</p>',
            status: ProductStatus::Published,
            isFeatured: true,
            position: 0,
            publishedAt: CarbonImmutable::now()->subMinute(),
            metaTitle: 'Updated Cache Product',
            metaDescription: 'Updated storefront cache test product.',
            categoryIds: [],
        );
    }
}
