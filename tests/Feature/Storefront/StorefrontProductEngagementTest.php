<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_shows_three_newest_approved_reviews_and_loads_more_in_batches(): void
    {
        $product = Product::factory()->published()->create();
        $reviews = ProductReview::factory()->approved()->count(7)->for($product)->create([
            'created_at' => now()->subMinute(),
        ])->reverse()->values();

        ProductReview::factory()->for($product)->create();
        ProductReview::factory()->rejected()->for($product)->create();
        ProductReview::factory()->approved()->create();

        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->component('Frontend/Products/Show')
                ->where('reviews.summary.review_count', 7)
                ->where('reviews.reviews.per_page', 3)
                ->where('reviews.reviews.last_page', 3)
                ->has('reviews.reviews.data', 3)
                ->where('reviews.reviews.data.0.id', $reviews[0]->id)
                ->where('reviews.reviews.data.2.id', $reviews[2]->id),
        );

        $url = '/products/'.$product->slug.'/reviews';
        $response = $this->getJson($url.'?page=2&per_page=1000')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('total', 7)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $reviews[3]->id)
            ->assertJsonPath('data.2.id', $reviews[5]->id)
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.moderation_note');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->getJson($url.'?page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reviews[6]->id);

        $this->getJson($url.'?page=4')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_review_feed_enforces_publication_and_valid_page_boundary(): void
    {
        $product = Product::factory()->published()->create();
        $url = '/products/'.$product->slug.'/reviews';

        foreach (['0', '-1', 'no', '10001', '1.5'] as $page) {
            $this->getJson($url.'?page='.$page)->assertUnprocessable();
        }

        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');

        foreach ([
            ['status' => ProductStatus::Draft],
            ['status' => ProductStatus::Archived],
            ['status' => ProductStatus::Published, 'published_at' => now()->addDay()],
        ] as $attributes) {
            $hidden = Product::factory()->create($attributes);
            ProductReview::factory()->approved()->for($hidden)->create();
            $this->getJson('/products/'.$hidden->slug.'/reviews')->assertNotFound();
        }
    }

    public function test_related_products_prioritize_active_categories_then_active_brand_without_leaking_private_fields(): void
    {
        $brand = Brand::factory()->create(['is_active' => true]);
        $category = Category::factory()->create(['is_active' => true]);
        $inactive = Category::factory()->create(['is_active' => false]);
        $product = Product::factory()->published()->for($brand)->create();
        $product->categories()->attach([$category->id, $inactive->id]);

        $categoryMatch = Product::factory()->published()->create(['published_at' => now()->subDay()]);
        $categoryMatch->categories()->attach($category->id);
        $both = Product::factory()->published()->for($brand)->create();
        $both->categories()->attach($category->id);
        $brandMatch = Product::factory()->published()->for($brand)->create(['cost_price' => 500]);
        $inactiveMatch = Product::factory()->published()->create();
        $inactiveMatch->categories()->attach($inactive->id);
        Product::factory()->published()->create();

        foreach ([
            ['status' => ProductStatus::Draft],
            ['status' => ProductStatus::Archived],
            ['status' => ProductStatus::Published, 'published_at' => now()->addDay()],
        ] as $attributes) {
            $hidden = Product::factory()->for($brand)->create($attributes);
            $hidden->categories()->attach($category->id);
        }

        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->has('relatedProducts', 3)
                ->where('relatedProducts.0.id', $both->id)
                ->where('relatedProducts.1.id', $categoryMatch->id)
                ->where('relatedProducts.2.id', $brandMatch->id)
                ->missing('relatedProducts.2.cost_price')
                ->missing('relatedProducts.2.stock_quantity')
                ->missing('relatedProducts.2.track_inventory'),
        );
    }

    public function test_related_products_are_bounded_and_respect_live_publication_changes(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->published()->create();
        $product->categories()->attach($category->id);
        $candidates = Product::factory()->published()->count(9)->create(['published_at' => now()->subMinute()]);

        foreach ($candidates as $candidate) {
            $candidate->categories()->attach($category->id);
        }

        $latest = $candidates->last();
        $this->assertNotNull($latest);

        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('relatedProducts', 8)->where('relatedProducts.0.id', $latest->id),
        );

        $latest->update(['status' => ProductStatus::Archived]);
        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('relatedProducts', 8)->where('relatedProducts.0.id', $candidates[7]->id),
        );
    }

    public function test_unrelated_products_and_inactive_brand_fallback_are_not_shown(): void
    {
        $brand = Brand::factory()->create(['is_active' => false]);
        $product = Product::factory()->published()->for($brand)->create();
        Product::factory()->published()->for($brand)->create();
        Product::factory()->published()->create();

        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('relatedProducts', 0),
        );
    }
}
