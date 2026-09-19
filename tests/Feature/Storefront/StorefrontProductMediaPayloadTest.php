<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductMediaPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_exposes_public_image_data(): void
    {
        $product = Product::factory()->create([
            'slug' => 'media-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/image.webp',
            'alt_text' => 'Front view',
            'width' => 1200,
            'height' => 1200,
            'is_primary' => true,
            'position' => 0,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.images', 1)
                    ->where(
                        'product.images.0.alt',
                        'Front view',
                    )
                    ->where(
                        'product.images.0.width',
                        1200,
                    )
                    ->where(
                        'product.images.0.height',
                        1200,
                    )
                    ->missing(
                        'product.images.0.path',
                    )
                    ->missing(
                        'product.images.0.original_name',
                    )
                    ->missing(
                        'product.images.0.mime_type',
                    )
                    ->missing(
                        'product.images.0.file_size',
                    ),
            );
    }

    public function test_uploaded_video_exposes_public_url_without_storage_path(): void
    {
        $product = Product::factory()->create([
            'slug' => 'uploaded-video-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVideo::factory()->create([
            'product_id' => $product->id,
            'type' => ProductVideoType::Upload,
            'path' => 'catalog/products/test/videos/product.mp4',
            'url' => null,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.video.type',
                        ProductVideoType::Upload->value,
                    )
                    ->where(
                        'product.video.url',
                        fn (mixed $url): bool => is_string($url)
                            && str_contains(
                                $url,
                                'product.mp4',
                            ),
                    )
                    ->missing('product.video.path'),
            );
    }

    public function test_hosted_video_exposes_validated_public_url(): void
    {
        $product = Product::factory()->create([
            'slug' => 'youtube-video-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        ProductVideo::factory()->create([
            'product_id' => $product->id,
            'type' => ProductVideoType::Youtube,
            'path' => null,
            'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.video.type',
                        ProductVideoType::Youtube->value,
                    )
                    ->where(
                        'product.video.url',
                        'https://www.youtube.com/watch?v=abcdefghijk',
                    )
                    ->missing('product.video.path'),
            );
    }

    public function test_product_without_video_returns_null_video(): void
    {
        $product = Product::factory()->create([
            'slug' => 'no-video-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('product.video', null),
            );
    }
}
