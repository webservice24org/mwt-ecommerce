<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\DeleteProductImageAction;
use App\Domain\Catalog\Actions\DeleteProductVideoAction;
use App\Domain\Catalog\Actions\ReorderProductImagesAction;
use App\Domain\Catalog\Actions\SaveProductVideoAction;
use App\Domain\Catalog\Actions\SetFeaturedProductImageAction;
use App\Domain\Catalog\Actions\UpdateProductImageAction;
use App\Domain\Catalog\Actions\UploadProductImageAction;
use App\Domain\Catalog\Data\CreateProductImageData;
use App\Domain\Catalog\Data\SaveProductVideoData;
use App\Domain\Catalog\Data\UpdateProductImageData;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StorefrontProductMediaMutationCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
    }

    public function test_uploading_product_image_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        $image = app(
            UploadProductImageAction::class,
        )->execute(
            $product,
            new CreateProductImageData(
                image: UploadedFile::fake()->image(
                    'cache-product.jpg',
                    1200,
                    1200,
                ),
                altText: 'Cache product image',
                isPrimary: false,
            ),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $image->id,
                'product_id' => $product->id,
            ],
        );

        $this->assertTrue(
            Storage::disk('public')->exists(
                $image->path,
            ),
        );
    }

    public function test_updating_product_image_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'alt_text' => 'Old alt text',
        ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        $updatedImage = app(
            UpdateProductImageAction::class,
        )->execute(
            $product,
            $image,
            new UpdateProductImageData(
                altText: 'Updated alt text',
            ),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertSame(
            'Updated alt text',
            $updatedImage->alt_text,
        );
    }

    public function test_setting_featured_product_image_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,
                'position' => 0,
            ]);

        $second = ProductImage::factory()->create([
            'product_id' => $product->id,
            'position' => 1,
            'is_primary' => false,
        ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        app(
            SetFeaturedProductImageAction::class,
        )->execute(
            $product,
            $second,
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertFalse(
            (bool) $first->refresh()->is_primary,
        );

        $this->assertTrue(
            (bool) $second->refresh()->is_primary,
        );
    }

    public function test_deleting_product_image_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $path =
            "catalog/products/{$product->id}/images/cache-delete.jpg";

        Storage::disk('public')->put(
            $path,
            'fake-image-content',
        );

        $image = ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => 0,
            ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        app(
            DeleteProductImageAction::class,
        )->execute(
            $product,
            $image,
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseMissing(
            'product_images',
            [
                'id' => $image->id,
            ],
        );

        $this->assertFalse(
            Storage::disk('public')->exists(
                $path,
            ),
        );
    }

    public function test_reordering_product_images_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()->create([
            'product_id' => $product->id,
            'position' => 0,
        ]);

        $second = ProductImage::factory()->create([
            'product_id' => $product->id,
            'position' => 1,
        ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        app(
            ReorderProductImagesAction::class,
        )->execute(
            $product,
            [
                $second->id,
                $first->id,
            ],
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertSame(
            1,
            $first->refresh()->position,
        );

        $this->assertSame(
            0,
            $second->refresh()->position,
        );
    }

    public function test_deleting_existing_product_video_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $path =
            "catalog/products/{$product->id}/videos/cache-video.mp4";

        Storage::disk('public')->put(
            $path,
            'fake-video-content',
        );

        $video = ProductVideo::query()->create([
            'product_id' => $product->id,
            'type' => ProductVideoType::Upload,
            'path' => $path,
            'url' => null,
            'original_name' => 'cache-video.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1024,
            'title' => 'Cache Video',
        ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        app(
            DeleteProductVideoAction::class,
        )->execute(
            $product,
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseMissing(
            'product_videos',
            [
                'id' => $video->id,
            ],
        );

        $this->assertFalse(
            Storage::disk('public')->exists(
                $path,
            ),
        );
    }

    public function test_deleting_when_product_has_no_video_does_not_invalidate_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        app(
            DeleteProductVideoAction::class,
        )->execute(
            $product,
        );

        $this->assertSame(
            $before,
            $cache->version(),
        );
    }

    public function test_saving_product_video_invalidates_storefront_cache(): void
    {
        $product = Product::factory()->create();

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        $video = app(
            SaveProductVideoAction::class,
        )->execute(
            $product,
            new SaveProductVideoData(
                type: ProductVideoType::Youtube,
                video: null,
                url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                title: 'Product Demo',
            ),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertDatabaseHas(
            'product_videos',
            [
                'id' => $video->id,
                'product_id' => $product->id,
                'type' => ProductVideoType::Youtube->value,
                'title' => 'Product Demo',
            ],
        );
    }

    public function test_replacing_product_video_invalidates_storefront_cache_once(): void
    {
        $product = Product::factory()->create();

        ProductVideo::query()->create([
            'product_id' => $product->id,
            'type' => ProductVideoType::Youtube,
            'path' => null,
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'original_name' => null,
            'mime_type' => null,
            'file_size' => null,
            'title' => 'Old Product Video',
        ]);

        $cache = app(StorefrontCatalogCache::class);

        $before = $cache->version();

        $video = app(
            SaveProductVideoAction::class,
        )->execute(
            $product,
            new SaveProductVideoData(
                type: ProductVideoType::Vimeo,
                video: null,
                url: 'https://vimeo.com/76979871',
                title: 'Updated Product Video',
            ),
        );

        $this->assertSame(
            $before + 1,
            $cache->version(),
        );

        $this->assertSame(
            'Updated Product Video',
            $video->title,
        );

        $this->assertSame(
            ProductVideoType::Vimeo,
            $video->type,
        );

        $this->assertDatabaseCount(
            'product_videos',
            1,
        );
    }
}
