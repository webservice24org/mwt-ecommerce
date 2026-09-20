<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontImageStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_listing_exposes_only_the_primary_image_on_each_card(): void
    {
        $product = Product::factory()->create([
            'name' => 'Image Strategy Product',
            'slug' => 'image-strategy-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $primary = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/primary.webp',
            'alt_text' => 'Primary product image',
            'position' => 0,
            'is_primary' => true,
            'width' => 1200,
            'height' => 1200,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/secondary.webp',
            'alt_text' => 'Secondary product image',
            'position' => 1,
            'is_primary' => false,
            'width' => 1200,
            'height' => 1200,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.image.id',
                        $primary->id,
                    )
                    ->where(
                        'products.data.0.image.alt',
                        'Primary product image',
                    )
                    ->where(
                        'products.data.0.image.width',
                        1200,
                    )
                    ->where(
                        'products.data.0.image.height',
                        1200,
                    )
                    ->missing('products.data.0.images')
                    ->missing(
                        'products.data.0.image.path',
                    )
                    ->missing(
                        'products.data.0.image.original_name',
                    )
                    ->missing(
                        'products.data.0.image.mime_type',
                    )
                    ->missing(
                        'products.data.0.image.file_size',
                    ),
            );
    }

    public function test_product_detail_preserves_the_complete_public_gallery(): void
    {
        $product = Product::factory()->create([
            'name' => 'Gallery Product',
            'slug' => 'gallery-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $primary = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/primary.webp',
            'alt_text' => 'Primary image',
            'position' => 0,
            'is_primary' => true,
            'width' => 1200,
            'height' => 1200,
        ]);

        $secondary = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'catalog/products/test/secondary.webp',
            'alt_text' => 'Secondary image',
            'position' => 1,
            'is_primary' => false,
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->get('/products/gallery-product')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('product.images', 2)
                    ->where(
                        'product.images.0.id',
                        $primary->id,
                    )
                    ->where(
                        'product.images.1.id',
                        $secondary->id,
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

    public function test_product_without_image_returns_null_card_image(): void
    {
        Product::factory()->create([
            'name' => 'No Image Product',
            'slug' => 'no-image-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.image',
                        null,
                    ),
            );
    }

    public function test_product_listing_does_not_query_primary_images_per_product(): void
    {
        Product::factory()
            ->count(8)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $queries = [];

        DB::listen(
            static function ($query) use (&$queries): void {
                $queries[] = $query->sql;
            },
        );

        $this->get('/products')->assertOk();

        $imageQueries = array_values(
            array_filter(
                $queries,
                static fn (string $sql): bool => str_contains(
                    strtolower($sql),
                    'product_images',
                ),
            ),
        );

        $this->assertLessThanOrEqual(
            2,
            count($imageQueries),
            'Product listing is querying product images per product.',
        );
    }

    public function test_storefront_listing_has_all_card_relations_eager_loaded(): void
    {
        Model::preventLazyLoading();

        try {
            Product::factory()
                ->count(8)
                ->create([
                    'status' => ProductStatus::Published,
                    'published_at' => now()->subMinute(),
                ]);

            $this->get('/products')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
