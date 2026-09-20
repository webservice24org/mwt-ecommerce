<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class StorefrontCategoryBrandVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_active_category_is_publicly_addressable(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $resolved = app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            $category->slug,
        );

        $this->assertSame(
            $category->id,
            $resolved->id,
        );
    }

    public function test_inactive_category_is_not_publicly_addressable(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $this->expectException(
            NotFoundHttpException::class,
        );

        app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            $category->slug,
        );
    }

    public function test_published_product_does_not_make_inactive_category_publicly_addressable(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = $this->createPublishedProduct();

        $product->categories()->attach(
            $category->id,
        );

        $this->expectException(
            NotFoundHttpException::class,
        );

        app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            $category->slug,
        );
    }

    public function test_active_category_with_public_product_appears_in_public_filter_options(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $product = $this->createPublishedProduct();

        $product->categories()->attach(
            $category->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertTrue(
            collect($options->categories)
                ->contains(
                    static fn ($option): bool => $option->id === $category->id,
                ),
        );
    }

    public function test_inactive_category_with_public_product_does_not_appear_in_public_filter_options(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = $this->createPublishedProduct();

        $product->categories()->attach(
            $category->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->categories)
                ->contains(
                    static fn ($option): bool => $option->id === $category->id,
                ),
        );
    }

    public function test_active_category_without_public_products_does_not_appear_in_public_filter_options(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $draft = Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $draft->categories()->attach(
            $category->id,
        );

        $future = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addHour(),
        ]);

        $future->categories()->attach(
            $category->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->categories)
                ->contains(
                    static fn ($option): bool => $option->id === $category->id,
                ),
        );
    }

    public function test_active_brand_with_public_product_appears_in_public_filter_options(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $this->createPublishedProduct(
            $brand,
        );

        $options = $this->freshFilterOptions();

        $this->assertTrue(
            collect($options->brands)
                ->contains(
                    static fn ($option): bool => $option->id === $brand->id,
                ),
        );
    }

    public function test_inactive_brand_with_public_product_does_not_appear_in_public_filter_options(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => false,
        ]);

        $this->createPublishedProduct(
            $brand,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->brands)
                ->contains(
                    static fn ($option): bool => $option->id === $brand->id,
                ),
        );
    }

    public function test_active_brand_without_public_products_does_not_appear_in_public_filter_options(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Draft,
                'published_at' => null,
            ]);

        Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Archived,
                'published_at' => now()->subHour(),
            ]);

        Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->addHour(),
            ]);

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->brands)
                ->contains(
                    static fn ($option): bool => $option->id === $brand->id,
                ),
        );
    }

    public function test_published_product_with_inactive_brand_remains_public_but_brand_is_not_filterable(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => false,
        ]);

        $product = $this->createPublishedProduct(
            $brand,
        );

        $detail = app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );

        $this->assertNotNull(
            $detail,
        );

        $this->assertSame(
            $product->id,
            $detail->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->brands)
                ->contains(
                    static fn ($option): bool => $option->id === $brand->id,
                ),
        );
    }

    public function test_category_cache_does_not_make_deactivated_category_remain_public_after_invalidation(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $resolved = app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            $category->slug,
        );

        $this->assertSame(
            $category->id,
            $resolved->id,
        );

        $category->update([
            'is_active' => false,
        ]);

        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $this->expectException(
            NotFoundHttpException::class,
        );

        app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            $category->slug,
        );
    }

    private function createPublishedProduct(
        ?Brand $brand = null,
    ): Product {
        $factory = Product::factory();

        if ($brand !== null) {
            $factory = $factory->for(
                $brand,
            );
        }

        return $factory->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }

    private function freshFilterOptions(): mixed
    {
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        return app(
            StorefrontFilterOptionsQuery::class,
        )->get();
    }
}
