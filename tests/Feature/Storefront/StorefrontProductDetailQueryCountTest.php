<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MeasuresDatabaseQueries;
use Tests\TestCase;

final class StorefrontProductDetailQueryCountTest extends TestCase
{
    use MeasuresDatabaseQueries;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_detail_query_count_does_not_grow_with_gallery_images(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->for($product)
            ->create([
                'is_primary' => true,
                'position' => 0,
            ]);

        $small = $this->measureColdDetailQueries(
            $product,
        );

        ProductImage::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_primary' => false,
            ]);

        $large = $this->measureColdDetailQueries(
            $product,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Product detail query count grew from %d to %d when gallery images increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_detail_query_count_does_not_grow_with_categories(): void
    {
        $product = $this->createPublishedProduct();

        $firstCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $firstCategory->id,
        );

        $small = $this->measureColdDetailQueries(
            $product,
        );

        $categories = Category::factory()
            ->count(12)
            ->create([
                'is_active' => true,
            ]);

        $product->categories()->attach(
            $categories->modelKeys(),
        );

        $large = $this->measureColdDetailQueries(
            $product,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Product detail query count grew from %d to %d when categories increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_detail_query_count_does_not_grow_with_variants(): void
    {
        $product = $this->createPublishedProduct();

        ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $small = $this->measureColdDetailQueries(
            $product,
        );

        ProductVariant::factory()
            ->count(12)
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => false,
            ]);

        $large = $this->measureColdDetailQueries(
            $product,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Product detail query count grew from %d to %d when active variants increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_detail_query_count_does_not_grow_with_variant_attribute_values(): void
    {
        $product = $this->createPublishedProduct();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $firstAttribute = ProductAttribute::factory()->create();

        $firstValue = AttributeValue::factory()
            ->for(
                $firstAttribute,
                'attribute',
            )
            ->create();

        $variant->attributeValues()->attach(
            $firstValue->id,
        );

        $small = $this->measureColdDetailQueries(
            $product,
        );

        /*
         * Each additional value belongs to a different attribute.
         * This is a valid variant combination and exercises both:
         *
         * variants.attributeValues
         * variants.attributeValues.attribute
         */
        for ($i = 0; $i < 8; $i++) {
            $attribute = ProductAttribute::factory()->create();

            $value = AttributeValue::factory()
                ->for(
                    $attribute,
                    'attribute',
                )
                ->create();

            $variant->attributeValues()->attach(
                $value->id,
            );
        }

        $large = $this->measureColdDetailQueries(
            $product,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Product detail query count grew from %d to %d when variant attribute values increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_warm_product_detail_cache_does_not_rebuild_product_from_database(): void
    {
        $product = $this->createPublishedProduct();

        ProductVariant::factory()
            ->count(5)
            ->for($product)
            ->create([
                'is_active' => true,
            ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        /*
         * Populate the product-detail cache before query
         * measurement begins.
         */
        $first = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull(
            $first,
        );

        $measurement = $this->measureDatabaseQueries(
            fn () => $query->findBySlug(
                $product->slug,
            ),
        );

        $this->assertNotNull(
            $measurement['result'],
        );

        $this->assertSame(
            0,
            $measurement['count'],
            sprintf(
                'Warm product-detail cache unexpectedly executed %d database queries.',
                $measurement['count'],
            ),
        );
    }

    public function test_cold_product_detail_query_count_is_stable_across_repeated_generations(): void
    {
        $product = $this->createPublishedProduct();

        ProductImage::factory()
            ->count(4)
            ->for($product)
            ->create();

        ProductVariant::factory()
            ->count(4)
            ->for($product)
            ->create([
                'is_active' => true,
            ]);

        $first = $this->measureColdDetailQueries(
            $product,
        );

        $second = $this->measureColdDetailQueries(
            $product,
        );

        $this->assertSame(
            $first,
            $second,
            sprintf(
                'Cold product-detail query count changed from %d to %d across equivalent cache generations.',
                $first,
                $second,
            ),
        );
    }

    private function measureColdDetailQueries(
        Product $product,
    ): int {
        /*
         * Move to a new storefront cache generation before
         * measuring so the product detail must be rebuilt.
         *
         * Invalidation itself stays outside the SQL measurement.
         */
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $measurement = $this->measureDatabaseQueries(
            fn () => app(
                StorefrontProductDetailQuery::class,
            )->findBySlug(
                $product->slug,
            ),
        );

        $this->assertNotNull(
            $measurement['result'],
        );

        return $measurement['count'];
    }

    private function createPublishedProduct(): Product
    {
        return Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
