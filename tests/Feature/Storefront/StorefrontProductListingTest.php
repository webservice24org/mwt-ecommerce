<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_page_returns_published_products(): void
    {
        Product::factory()->create([
            'name' => 'Public Product',
            'slug' => 'public-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Draft Product',
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Index',
                    )
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Public Product',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_shop_page_excludes_future_products(): void
    {
        Product::factory()->create([
            'name' => 'Current Product',
            'slug' => 'current-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        Product::factory()->create([
            'name' => 'Scheduled Product',
            'slug' => 'scheduled-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Current Product',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_shop_page_can_return_an_empty_product_collection(): void
    {
        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Index',
                    )
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_shop_page_is_paginated(): void
    {
        Product::factory()
            ->count(25)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 24)
                    ->where('products.current_page', 1)
                    ->where('products.last_page', 2)
                    ->where('products.per_page', 24)
                    ->where('products.total', 25),
            );

        $this->get('/products?page=2')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where('products.current_page', 2)
                    ->where('products.total', 25),
            );
    }

    public function test_pagination_links_preserve_existing_query_string(): void
    {
        Product::factory()
            ->count(25)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $response = $this->get(
            '/products?example=value',
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.next_page_url',
                        fn (
                            mixed $url,
                        ): bool => is_string($url)
                            && str_contains(
                                $url,
                                'example=value',
                            )
                            && str_contains(
                                $url,
                                'page=2',
                            ),
                    ),
            );
    }

    public function test_client_cannot_override_shop_page_size(): void
    {
        Product::factory()
            ->count(30)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $this->get('/products?per_page=10000')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 24)
                    ->where('products.per_page', 24)
                    ->where('products.total', 30),
            );
    }
}
