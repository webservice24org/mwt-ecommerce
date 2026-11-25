<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductPreferredSocialImagePayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_product_image_is_first_and_preserves_real_alt_and_dimensions(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Braided USB Cable',

                    'slug' => 'braided-usb-cable',

                    'price' => 85000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        ProductImage::factory()
            ->create([
                'product_id' => $product->id,

                'path' => 'products/secondary.jpg',

                'alt_text' => 'Cable connector close-up',

                'width' => 1000,

                'height' => 1000,

                'position' => 0,

                'is_primary' => false,
            ]);

        $primary =
            ProductImage::factory()
                ->create([
                    'product_id' => $product->id,

                    'path' => 'products/featured.jpg',

                    'alt_text' => 'Black braided USB cable on a white background',

                    'width' => 1600,

                    'height' => 1600,

                    'position' => 10,

                    'is_primary' => true,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.images.0.id',
                        $primary->id,
                    )
                    ->where(
                        'product.images.0.alt',
                        'Black braided USB cable on a white background',
                    )
                    ->where(
                        'product.images.0.width',
                        1600,
                    )
                    ->where(
                        'product.images.0.height',
                        1600,
                    ),
            );
    }

    public function test_blank_product_image_alt_remains_blank_instead_of_being_invented_server_side(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Plain Cable',

                    'slug' => 'plain-cable',

                    'price' => 70000,

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $primary =
            ProductImage::factory()
                ->create([
                    'product_id' => $product->id,

                    'path' => 'products/plain.jpg',

                    'alt_text' => null,

                    'width' => 1200,

                    'height' => 1200,

                    'position' => 0,

                    'is_primary' => true,
                ]);

        $this
            ->get(
                '/products/'
                .$product->slug,
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.images.0.id',
                        $primary->id,
                    )
                    ->where(
                        'product.images.0.alt',
                        null,
                    ),
            );
    }
}
