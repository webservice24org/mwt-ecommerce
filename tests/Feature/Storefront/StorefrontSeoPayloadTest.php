<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontSeoPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_exposes_public_seo_fields(): void
    {
        $product = Product::factory()->create([
            'slug' => 'seo-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'meta_title' => 'SEO Product Title',
            'meta_description' => 'SEO Product Description',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Products/Show')
                    ->where(
                        'product.meta_title',
                        'SEO Product Title',
                    )
                    ->where(
                        'product.meta_description',
                        'SEO Product Description',
                    ),
            );
    }

    public function test_product_detail_supports_null_optional_seo_fields(): void
    {
        $product = Product::factory()->create([
            'slug' => 'seo-fallback-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'product.meta_title',
                        null,
                    )
                    ->where(
                        'product.meta_description',
                        null,
                    ),
            );
    }

    public function test_category_page_exposes_public_seo_fields(): void
    {
        $category = Category::factory()->create([
            'slug' => 'seo-category',
            'is_active' => true,
            'meta_title' => 'SEO Category Title',
            'meta_description' => 'SEO Category Description',
        ]);

        $this->get('/category/'.$category->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Categories/Show',
                    )
                    ->where(
                        'category.meta_title',
                        'SEO Category Title',
                    )
                    ->where(
                        'category.meta_description',
                        'SEO Category Description',
                    ),
            );
    }

    public function test_inactive_category_is_not_publicly_accessible(): void
    {
        $category = Category::factory()->create([
            'slug' => 'inactive-seo-category',
            'is_active' => false,
        ]);

        $this->get('/category/'.$category->slug)
            ->assertNotFound();
    }

    public function test_draft_product_does_not_expose_its_seo_payload(): void
    {
        $product = Product::factory()->create([
            'slug' => 'draft-seo-product',
            'status' => ProductStatus::Draft,
            'meta_title' => 'Private Draft SEO',
            'meta_description' => 'Private draft description.',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertNotFound();
    }

    public function test_future_product_does_not_expose_its_seo_payload(): void
    {
        $product = Product::factory()->create([
            'slug' => 'future-seo-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
            'meta_title' => 'Future SEO',
            'meta_description' => 'Future product description.',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertNotFound();
    }
}
