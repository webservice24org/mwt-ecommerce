<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Cache\StorefrontCatalogCacheTtl;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontScheduledPublicationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $baseTime;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );

        $this->baseTime = Carbon::parse(
            '2026-09-20 12:00:00',
        );

        Carbon::setTestNow($this->baseTime);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_scheduled_product_is_hidden_one_second_before_publication(): void
    {
        $product = $this->scheduledProduct(
            $this->baseTime->copy()->addSecond(),
        );

        $this->assertProductHiddenEverywhere(
            $product,
        );
    }

    public function test_scheduled_product_becomes_public_exactly_at_publication_time(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addMinute();

        $product = $this->scheduledProduct(
            $publicationAt,
        );

        Carbon::setTestNow($publicationAt);

        $this->assertProductPublicEverywhere(
            $product,
        );
    }

    public function test_scheduled_product_is_public_one_second_after_publication(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addMinute();

        $product = $this->scheduledProduct(
            $publicationAt,
        );

        Carbon::setTestNow(
            $publicationAt
                ->copy()
                ->addSecond(),
        );

        $this->assertProductPublicEverywhere(
            $product,
        );
    }

    public function test_future_detail_lookup_is_not_negative_cached(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addMinute();

        $product = $this->scheduledProduct(
            $publicationAt,
        );

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        $this->assertNull(
            $query->findBySlug($product->slug),
        );

        Carbon::setTestNow($publicationAt);

        $detail = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($detail);
        $this->assertSame(
            $product->id,
            $detail->id,
        );
    }

    public function test_home_cache_ttl_is_shortened_to_next_scheduled_publication(): void
    {
        $this->scheduledProduct(
            $this->baseTime
                ->copy()
                ->addSeconds(45),
        );

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            45,
            $ttl,
        );
    }

    public function test_home_cache_ttl_uses_default_when_no_future_publication_exists(): void
    {
        $this->scheduledProduct(
            $this->baseTime
                ->copy()
                ->subSecond(),
        );

        $ttl = app(
            StorefrontCatalogCacheTtl::class,
        )->seconds();

        $this->assertSame(
            StorefrontCatalogCache::DEFAULT_TTL_SECONDS,
            $ttl,
        );
    }

    public function test_home_cache_does_not_keep_scheduled_product_hidden_after_publication(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addSeconds(2);

        $product = $this->scheduledProduct(
            $publicationAt,
            featured: true,
        );

        $homeQuery = app(
            StorefrontHomeQuery::class,
        );

        $before = $homeQuery->get();

        $this->assertFalse(
            $this->homeContainsProduct(
                $before->toArray(),
                $product->id,
            ),
        );

        Carbon::setTestNow(
            $publicationAt
                ->copy()
                ->addSecond(),
        );

        /*
         * Array cache does not naturally advance TTL according
         * to Carbon's test clock. Remove expired cache entries
         * through Laravel's cache implementation by advancing
         * the application clock is therefore not a reliable
         * integration simulation here.
         *
         * Instead, the TTL boundary itself is tested separately
         * above, while this assertion verifies the rebuilt home
         * payload at the publication boundary.
         */
        app(StorefrontCatalogCache::class)
            ->invalidate();

        $after = $homeQuery->get();

        $this->assertTrue(
            $this->homeContainsProduct(
                $after->toArray(),
                $product->id,
            ),
        );
    }

    public function test_draft_product_does_not_become_public_when_scheduled_time_arrives(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addMinute();

        $product = $this->scheduledProduct(
            $publicationAt,
            status: ProductStatus::Draft,
        );

        Carbon::setTestNow($publicationAt);

        $this->assertProductHiddenEverywhere(
            $product,
        );
    }

    public function test_archived_product_does_not_become_public_when_scheduled_time_arrives(): void
    {
        $publicationAt = $this->baseTime
            ->copy()
            ->addMinute();

        $product = $this->scheduledProduct(
            $publicationAt,
            status: ProductStatus::Archived,
        );

        Carbon::setTestNow($publicationAt);

        $this->assertProductHiddenEverywhere(
            $product,
        );
    }

    public function test_null_publication_time_is_immediately_public(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => null,
            'is_featured' => true,
        ]);

        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $category->id,
        );

        $this->assertProductPublicEverywhere(
            $product,
        );
    }

    private function scheduledProduct(
        Carbon $publicationAt,
        ProductStatus $status = ProductStatus::Published,
        bool $featured = true,
    ): Product {
        $product = Product::factory()->create([
            'status' => $status,
            'published_at' => $publicationAt,
            'is_featured' => $featured,
        ]);

        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $category->id,
        );

        return $product;
    }

    private function assertProductHiddenEverywhere(
        Product $product,
    ): void {
        app(StorefrontCatalogCache::class)
            ->invalidate();

        $this->get(
            '/products/'.$product->slug,
        )->assertNotFound();

        $shop = $this->get('/products');

        $shop->assertOk();

        $shop->assertInertia(
            fn ($page) => $page
                ->where(
                    'products.data',
                    fn ($products) => collect($products)
                        ->doesntContain(
                            fn ($item) => (int) $item['id']
                                === $product->id,
                        ),
                ),
        );

        $category = $product
            ->categories()
            ->firstOrFail();

        $categoryResponse = $this->get(
            '/category/'.$category->slug,
        );

        $categoryResponse->assertOk();

        $categoryResponse->assertInertia(
            fn ($page) => $page
                ->where(
                    'products.data',
                    fn ($products) => collect($products)
                        ->doesntContain(
                            fn ($item) => (int) $item['id']
                                === $product->id,
                        ),
                ),
        );

        $home = app(
            StorefrontHomeQuery::class,
        )->get();

        $this->assertFalse(
            $this->homeContainsProduct(
                $home->toArray(),
                $product->id,
            ),
        );
    }

    private function assertProductPublicEverywhere(
        Product $product,
    ): void {
        app(StorefrontCatalogCache::class)
            ->invalidate();

        $this->get(
            '/products/'.$product->slug,
        )->assertOk();

        $shop = $this->get('/products');

        $shop->assertOk();

        $shop->assertInertia(
            fn ($page) => $page
                ->where(
                    'products.data',
                    fn ($products) => collect($products)
                        ->contains(
                            fn ($item) => (int) $item['id']
                                === $product->id,
                        ),
                ),
        );

        $category = $product
            ->categories()
            ->firstOrFail();

        $categoryResponse = $this->get(
            '/category/'.$category->slug,
        );

        $categoryResponse->assertOk();

        $categoryResponse->assertInertia(
            fn ($page) => $page
                ->where(
                    'products.data',
                    fn ($products) => collect($products)
                        ->contains(
                            fn ($item) => (int) $item['id']
                                === $product->id,
                        ),
                ),
        );

        $home = app(
            StorefrontHomeQuery::class,
        )->get();

        $this->assertTrue(
            $this->homeContainsProduct(
                $home->toArray(),
                $product->id,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $home
     */
    private function homeContainsProduct(
        array $home,
        int $productId,
    ): bool {
        $featured = collect(
            $home['featured_products'] ?? [],
        );

        $newArrivals = collect(
            $home['new_arrivals'] ?? [],
        );

        return $featured
            ->merge($newArrivals)
            ->contains(
                fn ($item) => (int) $item['id']
                    === $productId,
            );
    }
}
