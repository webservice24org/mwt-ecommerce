<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\StorefrontProductSort;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryProductQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MeasuresDatabaseQueries;
use Tests\TestCase;

final class StorefrontCategoryProductQueryCountTest extends TestCase
{
    use MeasuresDatabaseQueries;
    use RefreshDatabase;

    public function test_category_listing_query_count_does_not_grow_with_product_count(): void
    {
        $category = $this->createCategory();

        $this->createPublishedProducts(
            category: $category,
            count: 1,
        );

        $small = $this->measureCategoryListingQueries(
            $category,
        );

        /*
         * Keep the total below the 24-item page size so both
         * measurements exercise the same paginator shape.
         */
        $this->createPublishedProducts(
            category: $category,
            count: 15,
        );

        $large = $this->measureCategoryListingQueries(
            $category,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Category listing query count grew from %d to %d when product count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_category_listing_query_count_does_not_grow_with_variant_count(): void
    {
        $category = $this->createCategory();

        $product = $this->createPublishedProduct(
            category: $category,
        );

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $small = $this->measureCategoryListingQueries(
            $category,
        );

        ProductVariant::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => false,
            ]);

        $large = $this->measureCategoryListingQueries(
            $category,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Category listing query count grew from %d to %d when active variants increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_category_listing_query_count_does_not_grow_with_product_images(): void
    {
        $category = $this->createCategory();

        $product = $this->createPublishedProduct(
            category: $category,
        );

        ProductImage::factory()
            ->for($product)
            ->create([
                'is_primary' => true,
                'position' => 0,
            ]);

        $small = $this->measureCategoryListingQueries(
            $category,
        );

        ProductImage::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_primary' => false,
            ]);

        $large = $this->measureCategoryListingQueries(
            $category,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Category listing query count grew from %d to %d when gallery images increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_category_listing_query_count_does_not_grow_with_brand_reuse(): void
    {
        $category = $this->createCategory();

        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $this->createPublishedProduct(
            category: $category,
            brand: $brand,
        );

        $small = $this->measureCategoryListingQueries(
            $category,
        );

        $this->createPublishedProducts(
            category: $category,
            count: 15,
            brand: $brand,
        );

        $large = $this->measureCategoryListingQueries(
            $category,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Category listing query count grew from %d to %d when branded products increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_products_outside_the_category_do_not_change_category_listing_query_count(): void
    {
        $category = $this->createCategory();

        $this->createPublishedProducts(
            category: $category,
            count: 5,
        );

        $before = $this->measureCategoryListingQueries(
            $category,
        );

        $otherCategory = $this->createCategory();

        $this->createPublishedProducts(
            category: $otherCategory,
            count: 15,
        );

        $after = $this->measureCategoryListingQueries(
            $category,
        );

        $this->assertSame(
            $before,
            $after,
            sprintf(
                'Category listing query count changed from %d to %d because unrelated products were added.',
                $before,
                $after,
            ),
        );
    }

    private function measureCategoryListingQueries(
        Category $category,
    ): int {
        $measurement = $this->measureDatabaseQueries(
            fn () => app(
                StorefrontCategoryProductQuery::class,
            )->paginate(
                $category,
                $this->filters(),
            ),
        );

        return $measurement['count'];
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

    private function createCategory(): Category
    {
        return Category::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createPublishedProduct(
        Category $category,
        ?Brand $brand = null,
    ): Product {
        $brand ??= Brand::factory()->create([
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        $product->categories()->attach(
            $category->id,
        );

        return $product;
    }

    private function createPublishedProducts(
        Category $category,
        int $count,
        ?Brand $brand = null,
    ): void {
        $brand ??= Brand::factory()->create([
            'is_active' => true,
        ]);

        $products = Product::factory()
            ->count($count)
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        foreach ($products as $product) {
            $product->categories()->attach(
                $category->id,
            );
        }
    }
}
