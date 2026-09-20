<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\StorefrontProductSort;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryProductQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductListingQuery;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontProductPublicationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );

        $this->now = CarbonImmutable::parse(
            '2026-09-20 12:00:00',
        );

        CarbonImmutable::setTestNow(
            $this->now,
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_published_product_with_null_publication_date_is_public_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Published,
            publishedAt: null,
            featured: true,
        );

        $this->assertProductIsPublicEverywhere(
            $product,
        );
    }

    public function test_published_product_with_past_publication_date_is_public_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Published,
            publishedAt: $this->now->subMinute(),
            featured: true,
        );

        $this->assertProductIsPublicEverywhere(
            $product,
        );
    }

    public function test_published_product_at_exact_publication_boundary_is_public_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Published,
            publishedAt: $this->now,
            featured: true,
        );

        $this->assertProductIsPublicEverywhere(
            $product,
        );
    }

    public function test_future_published_product_is_hidden_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Published,
            publishedAt: $this->now->addMinute(),
            featured: true,
        );

        $this->assertProductIsHiddenEverywhere(
            $product,
        );
    }

    public function test_draft_product_with_null_publication_date_is_hidden_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Draft,
            publishedAt: null,
            featured: true,
        );

        $this->assertProductIsHiddenEverywhere(
            $product,
        );
    }

    public function test_draft_product_with_past_publication_date_is_hidden_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Draft,
            publishedAt: $this->now->subMinute(),
            featured: true,
        );

        $this->assertProductIsHiddenEverywhere(
            $product,
        );
    }

    public function test_archived_product_with_null_publication_date_is_hidden_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Archived,
            publishedAt: null,
            featured: true,
        );

        $this->assertProductIsHiddenEverywhere(
            $product,
        );
    }

    public function test_archived_product_with_past_publication_date_is_hidden_everywhere(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Archived,
            publishedAt: $this->now->subMinute(),
            featured: true,
        );

        $this->assertProductIsHiddenEverywhere(
            $product,
        );
    }

    public function test_non_featured_published_product_is_public_but_not_in_featured_home_section(): void
    {
        $product = $this->createProduct(
            status: ProductStatus::Published,
            publishedAt: $this->now->subMinute(),
            featured: false,
        );

        $category = $this->attachActiveCategory(
            $product,
        );

        $this->assertTrue(
            $this->shopContains($product),
        );

        $this->assertTrue(
            $this->categoryContains(
                $category,
                $product,
            ),
        );

        $this->assertNotNull(
            app(
                StorefrontProductDetailQuery::class,
            )->findBySlug($product->slug),
        );

        $home = $this->freshHome();

        $this->assertFalse(
            collect($home->featuredProducts)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
        );

        $this->assertTrue(
            collect($home->newArrivals)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
        );
    }

    private function assertProductIsPublicEverywhere(
        Product $product,
    ): void {
        $category = $this->attachActiveCategory(
            $product,
        );

        $this->assertTrue(
            $this->shopContains($product),
            'Expected product to be visible in the public shop listing.',
        );

        $this->assertTrue(
            $this->categoryContains(
                $category,
                $product,
            ),
            'Expected product to be visible in its public category listing.',
        );

        $detail = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNotNull(
            $detail,
            'Expected product detail query to return the public product.',
        );

        $this->assertSame(
            $product->id,
            $detail->id,
        );

        $home = $this->freshHome();

        $this->assertTrue(
            collect($home->featuredProducts)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
            'Expected featured public product to appear in the home featured section.',
        );

        $this->assertTrue(
            collect($home->newArrivals)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
            'Expected public product to appear in home new arrivals.',
        );
    }

    private function assertProductIsHiddenEverywhere(
        Product $product,
    ): void {
        $category = $this->attachActiveCategory(
            $product,
        );

        $this->assertFalse(
            $this->shopContains($product),
            'Non-public product leaked into the public shop listing.',
        );

        $this->assertFalse(
            $this->categoryContains(
                $category,
                $product,
            ),
            'Non-public product leaked into a public category listing.',
        );

        $this->assertNull(
            app(
                StorefrontProductDetailQuery::class,
            )->findBySlug(
                $product->slug,
            ),
            'Non-public product leaked through the public product detail query.',
        );

        $home = $this->freshHome();

        $this->assertFalse(
            collect($home->featuredProducts)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
            'Non-public product leaked into the home featured section.',
        );

        $this->assertFalse(
            collect($home->newArrivals)
                ->contains(
                    static fn ($item): bool => $item->id === $product->id,
                ),
            'Non-public product leaked into home new arrivals.',
        );
    }

    private function shopContains(
        Product $product,
    ): bool {
        return app(
            StorefrontProductListingQuery::class,
        )
            ->build(
                $this->filters(),
            )
            ->where(
                'products.id',
                $product->id,
            )
            ->exists();
    }

    private function categoryContains(
        Category $category,
        Product $product,
    ): bool {
        $paginator = app(
            StorefrontCategoryProductQuery::class,
        )->paginate(
            category: $category,
            filters: $this->filters(),
        );

        return collect(
            $paginator->items(),
        )->contains(
            static fn ($item): bool => $item->id === $product->id,
        );
    }

    private function freshHome(): mixed
    {
        /*
         * Home data is intentionally cached. Move to a fresh
         * generation so every assertion tests current database
         * publication state rather than an earlier test read.
         */
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        return app(
            StorefrontHomeQuery::class,
        )->get();
    }

    private function attachActiveCategory(
        Product $product,
    ): Category {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $category->id,
        );

        return $category;
    }

    private function createProduct(
        ProductStatus $status,
        ?CarbonImmutable $publishedAt,
        bool $featured,
    ): Product {
        return Product::factory()->create([
            'status' => $status,
            'published_at' => $publishedAt,
            'is_featured' => $featured,
        ]);
    }

    private function filters(): StorefrontProductFiltersData
    {
        return new StorefrontProductFiltersData(
            sort: StorefrontProductSort::Newest,
            brand: null,
            category: null,
            minPrice: null,
            maxPrice: null,
            attributes: [],
        );
    }
}
