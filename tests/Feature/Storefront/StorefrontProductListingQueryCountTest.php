<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\StorefrontProductSort;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductIndexQuery;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MeasuresDatabaseQueries;
use Tests\TestCase;

final class StorefrontProductListingQueryCountTest extends TestCase
{
    use MeasuresDatabaseQueries;
    use RefreshDatabase;

    public function test_shop_listing_query_count_does_not_grow_with_product_count(): void
    {
        $this->createPublishedProducts(
            count: 1,
        );

        $small = $this->measureListingQueries();

        /*
         * Keep both measurements below the 24-item page size so
         * we're comparing the same paginator shape while greatly
         * increasing the number of hydrated products.
         */
        $this->createPublishedProducts(
            count: 15,
        );

        $large = $this->measureListingQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Shop listing query count grew from %d to %d when the number of products increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_shop_listing_query_count_does_not_grow_with_variant_count(): void
    {
        $product = $this->createPublishedProduct();

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $small = $this->measureListingQueries();

        ProductVariant::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => false,
            ]);

        $large = $this->measureListingQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Shop listing query count grew from %d to %d when active variants increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_shop_listing_query_count_does_not_grow_with_product_images(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->for($product)
            ->create([
                'is_primary' => true,
                'position' => 0,
            ]);

        $small = $this->measureListingQueries();

        ProductImage::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_primary' => false,
            ]);

        $large = $this->measureListingQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Shop listing query count grew from %d to %d when gallery images increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_shop_listing_query_count_does_not_grow_with_brand_reuse(): void
    {
        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $this->createPublishedProduct(
            brand: $brand,
        );

        $small = $this->measureListingQueries();

        $this->createPublishedProducts(
            count: 15,
            brand: $brand,
        );

        $large = $this->measureListingQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Shop listing query count grew from %d to %d when branded products increased.',
                $small,
                $large,
            ),
        );
    }

    private function measureListingQueries(): int
    {
        $measurement = $this->measureDatabaseQueries(
            fn () => app(
                StorefrontProductIndexQuery::class,
            )->paginate(
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

    private function createPublishedProduct(
        ?Brand $brand = null,
    ): Product {
        return Product::factory()
            ->for(
                $brand ?? Brand::factory(),
            )
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);
    }

    private function createPublishedProducts(
        int $count,
        ?Brand $brand = null,
    ): void {
        $brand ??= Brand::factory()->create([
            'is_active' => true,
        ]);

        Product::factory()
            ->count($count)
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);
    }
}
