<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_category_page_is_publicly_accessible(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $this->get("/category/{$category->slug}")
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Categories/Show')
                    ->where('category.id', $category->id)
                    ->where('category.name', 'Electronics')
                    ->where('category.slug', 'electronics'),
            );
    }

    public function test_inactive_category_returns_not_found(): void
    {
        $category = Category::factory()->create([
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        $this->get("/category/{$category->slug}")
            ->assertNotFound();
    }

    public function test_category_only_contains_products_attached_to_that_category(): void
    {
        $electronics = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $clothing = Category::factory()->create([
            'slug' => 'clothing',
            'is_active' => true,
        ]);

        $electronicsProduct = Product::factory()->create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $clothingProduct = Product::factory()->create([
            'name' => 'T Shirt',
            'slug' => 't-shirt',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $electronicsProduct
            ->categories()
            ->attach($electronics);

        $clothingProduct
            ->categories()
            ->attach($clothing);

        $this->get('/category/electronics')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Laptop',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_category_does_not_expose_draft_or_future_products(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $published = Product::factory()->create([
            'name' => 'Public Laptop',
            'slug' => 'public-laptop',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $draft = Product::factory()->create([
            'name' => 'Draft Laptop',
            'slug' => 'draft-laptop',
            'status' => ProductStatus::Draft,
        ]);

        $future = Product::factory()->create([
            'name' => 'Future Laptop',
            'slug' => 'future-laptop',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $category->products()->attach([
            $published->id,
            $draft->id,
            $future->id,
        ]);

        $this->get('/category/electronics')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where(
                        'products.data.0.name',
                        'Public Laptop',
                    )
                    ->where('products.total', 1),
            );
    }

    public function test_active_category_without_public_products_returns_empty_listing(): void
    {
        $category = Category::factory()->create([
            'slug' => 'empty-category',
            'is_active' => true,
        ]);

        $this->get("/category/{$category->slug}")
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('products.total', 0),
            );
    }

    public function test_category_products_are_paginated(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $products = Product::factory()
            ->count(25)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $category->products()->attach(
            $products->modelKeys(),
        );

        $this->get('/category/electronics')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 24)
                    ->where('products.current_page', 1)
                    ->where('products.last_page', 2)
                    ->where('products.per_page', 24)
                    ->where('products.total', 25),
            );

        $this->get('/category/electronics?page=2')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 1)
                    ->where('products.current_page', 2)
                    ->where('products.total', 25),
            );
    }

    public function test_client_cannot_override_category_page_size(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $products = Product::factory()
            ->count(30)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $category->products()->attach(
            $products->modelKeys(),
        );

        $this->get(
            '/category/electronics?per_page=10000',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('products.data', 24)
                    ->where('products.per_page', 24)
                    ->where('products.total', 30),
            );
    }

    public function test_category_pagination_preserves_query_string(): void
    {
        $category = Category::factory()->create([
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $products = Product::factory()
            ->count(25)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $category->products()->attach(
            $products->modelKeys(),
        );

        $this->get(
            '/category/electronics?example=value',
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.next_page_url',
                        fn (mixed $url): bool => is_string($url)
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
}
