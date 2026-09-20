<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_storefront_payload(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Frontend/Home')
                    ->has('home.featured_products')
                    ->has('home.new_arrivals')
                    ->has('home.categories'),
            );
    }

    public function test_homepage_only_exposes_published_featured_products(): void
    {
        $visible = Product::factory()->create([
            'name' => 'Visible Featured Product',
            'slug' => 'visible-featured-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'is_featured' => true,
        ]);

        Product::factory()->create([
            'name' => 'Draft Featured Product',
            'slug' => 'draft-featured-product',
            'status' => ProductStatus::Draft,
            'is_featured' => true,
        ]);

        Product::factory()->create([
            'name' => 'Future Featured Product',
            'slug' => 'future-featured-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
            'is_featured' => true,
        ]);

        Product::factory()->create([
            'name' => 'Archived Featured Product',
            'slug' => 'archived-featured-product',
            'status' => ProductStatus::Archived,
            'is_featured' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.featured_products', 1)
                    ->where(
                        'home.featured_products.0.id',
                        $visible->id,
                    ),
            );
    }

    public function test_new_arrivals_only_expose_published_products(): void
    {
        $visible = Product::factory()->create([
            'name' => 'Visible New Product',
            'slug' => 'visible-new-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'is_featured' => false,
        ]);

        Product::factory()->create([
            'name' => 'Future Product',
            'slug' => 'future-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        Product::factory()->create([
            'name' => 'Draft Product',
            'slug' => 'draft-product',
            'status' => ProductStatus::Draft,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.new_arrivals', 1)
                    ->where(
                        'home.new_arrivals.0.id',
                        $visible->id,
                    ),
            );
    }

    public function test_homepage_only_exposes_active_top_level_categories(): void
    {
        $visible = Category::factory()->create([
            'name' => 'Visible Category',
            'slug' => 'visible-category',
            'parent_id' => null,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Inactive Category',
            'slug' => 'inactive-category',
            'parent_id' => null,
            'is_active' => false,
        ]);

        Category::factory()->create([
            'name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $visible->id,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.categories', 1)
                    ->where(
                        'home.categories.0.id',
                        $visible->id,
                    ),
            );
    }

    public function test_homepage_respects_section_limits(): void
    {
        Product::factory()
            ->count(10)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'is_featured' => true,
            ]);

        Category::factory()
            ->count(8)
            ->create([
                'parent_id' => null,
                'is_active' => true,
            ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.featured_products', 8)
                    ->has('home.new_arrivals', 8)
                    ->has('home.categories', 6),
            );
    }

    public function test_empty_catalog_still_returns_successful_homepage(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.featured_products', 0)
                    ->has('home.new_arrivals', 0)
                    ->has('home.categories', 0),
            );
    }

    public function test_homepage_product_cards_do_not_expose_internal_fields(): void
    {
        Product::factory()->create([
            'slug' => 'secure-home-product',
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
            'is_featured' => true,
            'cost_price' => 5000,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('home.featured_products', 1)
                    ->missing(
                        'home.featured_products.0.cost_price',
                    )
                    ->missing(
                        'home.featured_products.0.status',
                    )
                    ->missing(
                        'home.featured_products.0.published_at',
                    ),
            );
    }
}
