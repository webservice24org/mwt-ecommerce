<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontMediaVisibilitySecurityTest extends TestCase
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

    public function test_public_image_payload_contains_only_intended_fields(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/security/images/public.jpg',
                'original_name' => 'PRIVATE-ORIGINAL-NAME.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 987654,
                'width' => 1600,
                'height' => 1200,
                'alt_text' => 'Public product image',
                'position' => 77,
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);
        $this->assertCount(1, $detail->images);

        $payload = $detail->images[0]->toArray();

        $this->assertSame(
            [
                'id',
                'url',
                'alt',
                'width',
                'height',
            ],
            array_keys($payload),
        );

        $this->assertSame(
            'Public product image',
            $payload['alt'],
        );

        $this->assertSame(
            1600,
            $payload['width'],
        );

        $this->assertSame(
            1200,
            $payload['height'],
        );

        $this->assertStringContainsString(
            'catalog/products/security/images/public.jpg',
            $payload['url'],
        );

        $this->assertArrayNotHasKey('path', $payload);
        $this->assertArrayNotHasKey('original_name', $payload);
        $this->assertArrayNotHasKey('mime_type', $payload);
        $this->assertArrayNotHasKey('file_size', $payload);
        $this->assertArrayNotHasKey('position', $payload);
        $this->assertArrayNotHasKey('is_primary', $payload);
        $this->assertArrayNotHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('updated_at', $payload);

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-ORIGINAL-NAME',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'image/jpeg',
            $encoded,
        );
    }

    public function test_multiple_public_images_are_exposed_without_internal_metadata(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/security/images/primary.jpg',
                'original_name' => 'private-primary.jpg',
                'position' => 0,
            ]);

        ProductImage::factory()
            ->for($product)
            ->create([
                'path' => 'catalog/products/security/images/second.jpg',
                'original_name' => 'private-second.jpg',
                'position' => 1,
            ]);

        ProductImage::factory()
            ->for($product)
            ->create([
                'path' => 'catalog/products/security/images/third.jpg',
                'original_name' => 'private-third.jpg',
                'position' => 2,
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);
        $this->assertCount(3, $detail->images);

        foreach ($detail->images as $image) {
            $payload = $image->toArray();

            $this->assertSame(
                [
                    'id',
                    'url',
                    'alt',
                    'width',
                    'height',
                ],
                array_keys($payload),
            );
        }

        $encoded = json_encode(
            $detail->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'private-primary.jpg',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'private-second.jpg',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'private-third.jpg',
            $encoded,
        );
    }

    public function test_product_without_images_has_empty_public_gallery(): void
    {
        $product = $this->createPublishedProduct();

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);

        $this->assertSame(
            [],
            $detail->images,
        );
    }

    public function test_youtube_video_exposes_only_public_type_and_url(): void
    {
        $product = $this->createPublishedProduct();

        ProductVideo::factory()
            ->for($product)
            ->create([
                'type' => ProductVideoType::Youtube,
                'path' => null,
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'original_name' => 'PRIVATE-VIDEO-NAME.mp4',
                'mime_type' => 'video/private',
                'file_size' => 123456,
                'title' => 'PRIVATE INTERNAL VIDEO TITLE',
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);
        $this->assertNotNull($detail->video);

        $payload = $detail->video->toArray();

        $this->assertSame(
            [
                'type',
                'url',
            ],
            array_keys($payload),
        );

        $this->assertSame(
            ProductVideoType::Youtube->value,
            $payload['type'],
        );

        $this->assertSame(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            $payload['url'],
        );

        $this->assertArrayNotHasKey('path', $payload);
        $this->assertArrayNotHasKey('original_name', $payload);
        $this->assertArrayNotHasKey('mime_type', $payload);
        $this->assertArrayNotHasKey('file_size', $payload);
        $this->assertArrayNotHasKey('title', $payload);
        $this->assertArrayNotHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('updated_at', $payload);

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-VIDEO-NAME',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE INTERNAL VIDEO TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'video/private',
            $encoded,
        );
    }

    public function test_vimeo_video_exposes_only_public_type_and_url(): void
    {
        $product = $this->createPublishedProduct();

        ProductVideo::factory()
            ->for($product)
            ->create([
                'type' => ProductVideoType::Vimeo,
                'path' => null,
                'url' => 'https://vimeo.com/123456789',
                'original_name' => null,
                'mime_type' => null,
                'file_size' => null,
                'title' => 'PRIVATE VIMEO TITLE',
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);
        $this->assertNotNull($detail->video);

        $payload = $detail->video->toArray();

        $this->assertSame(
            [
                'type',
                'url',
            ],
            array_keys($payload),
        );

        $this->assertSame(
            ProductVideoType::Vimeo->value,
            $payload['type'],
        );

        $this->assertSame(
            'https://vimeo.com/123456789',
            $payload['url'],
        );

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE VIMEO TITLE',
            $encoded,
        );
    }

    public function test_uploaded_video_exposes_public_storage_url_not_internal_path_field(): void
    {
        $product = $this->createPublishedProduct();

        ProductVideo::factory()
            ->for($product)
            ->uploaded()
            ->create([
                'path' => 'catalog/products/security/videos/private-file.mp4',
                'url' => null,
                'original_name' => 'PRIVATE-UPLOAD-NAME.mp4',
                'mime_type' => 'video/mp4',
                'file_size' => 999999,
                'title' => 'PRIVATE UPLOAD TITLE',
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);
        $this->assertNotNull($detail->video);

        $payload = $detail->video->toArray();

        $this->assertSame(
            [
                'type',
                'url',
            ],
            array_keys($payload),
        );

        $this->assertSame(
            ProductVideoType::Upload->value,
            $payload['type'],
        );

        $this->assertNotNull(
            $payload['url'],
        );

        $this->assertStringContainsString(
            'catalog/products/security/videos/private-file.mp4',
            $payload['url'],
        );

        $this->assertArrayNotHasKey(
            'path',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'original_name',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'mime_type',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'file_size',
            $payload,
        );

        $this->assertArrayNotHasKey(
            'title',
            $payload,
        );

        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-UPLOAD-NAME.mp4',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'PRIVATE UPLOAD TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'video/mp4',
            $encoded,
        );
    }

    public function test_product_without_video_exposes_null_video(): void
    {
        $product = $this->createPublishedProduct();

        $detail = $this->freshDetail($product);

        $this->assertNotNull($detail);

        $this->assertNull(
            $detail->video,
        );
    }

    public function test_draft_product_cannot_expose_its_media_through_public_detail(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/private/images/draft-secret.jpg',
            ]);

        ProductVideo::factory()
            ->for($product)
            ->create([
                'url' => 'https://www.youtube.com/watch?v=draft-secret',
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNull(
            $detail,
        );
    }

    public function test_future_product_cannot_expose_its_media_through_public_detail(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addHour(),
        ]);

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/private/images/future-secret.jpg',
            ]);

        ProductVideo::factory()
            ->for($product)
            ->create([
                'url' => 'https://www.youtube.com/watch?v=future-secret',
            ]);

        $detail = $this->freshDetail($product);

        $this->assertNull(
            $detail,
        );
    }

    public function test_cached_detail_preserves_media_security_boundary(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->for($product)
            ->primary()
            ->create([
                'path' => 'catalog/products/security/images/cache-public.jpg',
                'original_name' => 'CACHE-PRIVATE-IMAGE.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 765432,
                'position' => 99,
            ]);

        ProductVideo::factory()
            ->for($product)
            ->uploaded()
            ->create([
                'path' => 'catalog/products/security/videos/cache-public.mp4',
                'original_name' => 'CACHE-PRIVATE-VIDEO.mp4',
                'mime_type' => 'video/mp4',
                'file_size' => 1234567,
                'title' => 'CACHE PRIVATE TITLE',
            ]);

        $cache = app(
            StorefrontCatalogCache::class,
        );

        $cache->invalidate();

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

        $encoded = json_encode(
            $second->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString(
            'cache-public.jpg',
            $encoded,
        );

        $this->assertStringContainsString(
            'cache-public.mp4',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'CACHE-PRIVATE-IMAGE.jpg',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'CACHE-PRIVATE-VIDEO.mp4',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'CACHE PRIVATE TITLE',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '"mime_type"',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '"file_size"',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '"is_primary"',
            $encoded,
        );

        $this->assertStringNotContainsString(
            '"position"',
            $encoded,
        );
    }

    private function createPublishedProduct(): Product
    {
        return Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
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
