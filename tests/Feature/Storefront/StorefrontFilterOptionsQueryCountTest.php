<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MeasuresDatabaseQueries;
use Tests\TestCase;

final class StorefrontFilterOptionsQueryCountTest extends TestCase
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

    public function test_filter_options_query_count_does_not_grow_with_brand_count(): void
    {
        $this->createPublishedProductWithBrand();

        $small = $this->measureColdFilterQueries();

        for ($i = 0; $i < 12; $i++) {
            $this->createPublishedProductWithBrand();
        }

        $large = $this->measureColdFilterQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Filter-option query count grew from %d to %d when brand count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_filter_options_query_count_does_not_grow_with_category_count(): void
    {
        $product = $this->createPublishedProductWithBrand();

        $first = Category::factory()->create([
            'is_active' => true,
        ]);

        $product->categories()->attach(
            $first->id,
        );

        $small = $this->measureColdFilterQueries();

        $categories = Category::factory()
            ->count(12)
            ->create([
                'is_active' => true,
            ]);

        $product->categories()->attach(
            $categories->modelKeys(),
        );

        $large = $this->measureColdFilterQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Filter-option query count grew from %d to %d when category count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_filter_options_query_count_does_not_grow_with_attribute_count(): void
    {
        $product = $this->createPublishedProductWithBrand();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $this->attachAttributeValue(
            $variant,
        );

        $small = $this->measureColdFilterQueries();

        for ($i = 0; $i < 12; $i++) {
            $this->attachAttributeValue(
                $variant,
            );
        }

        $large = $this->measureColdFilterQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Filter-option query count grew from %d to %d when attribute count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_filter_options_query_count_does_not_grow_with_attribute_value_count(): void
    {
        $product = $this->createPublishedProductWithBrand();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
            ]);

        $attribute = ProductAttribute::factory()->create([
            'is_active' => true,
        ]);

        $firstValue = AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'is_active' => true,
            ]);

        $variant->attributeValues()->attach(
            $firstValue->id,
        );

        $small = $this->measureColdFilterQueries();

        /*
         * These values are attached to additional variants because
         * a single variant must not contain multiple values from
         * the same attribute.
         */
        for ($i = 0; $i < 12; $i++) {
            $value = AttributeValue::factory()
                ->for(
                    $attribute,
                    'attribute',
                )
                ->create([
                    'is_active' => true,
                ]);

            $additionalVariant = ProductVariant::factory()
                ->for($product)
                ->create([
                    'is_active' => true,
                    'is_default' => false,
                ]);

            $additionalVariant
                ->attributeValues()
                ->attach($value->id);
        }

        $large = $this->measureColdFilterQueries();

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Filter-option query count grew from %d to %d when attribute-value count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_category_scoped_filter_query_count_does_not_grow_with_product_count(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $this->createPublishedProductWithBrand(
            $category,
        );

        $small = $this->measureColdFilterQueries(
            $category,
            false,
        );

        for ($i = 0; $i < 12; $i++) {
            $this->createPublishedProductWithBrand(
                $category,
            );
        }

        $large = $this->measureColdFilterQueries(
            $category,
            false,
        );

        $this->assertSame(
            $small,
            $large,
            sprintf(
                'Category-scoped filter query count grew from %d to %d when product count increased.',
                $small,
                $large,
            ),
        );
    }

    public function test_warm_filter_options_cache_only_checks_publication_ttl(): void
    {
        $this->createPublishedProductWithBrand();

        $query = app(
            StorefrontFilterOptionsQuery::class,
        );

        $first = $query->get();

        $this->assertNotEmpty(
            $first->brands,
        );

        $measurement = $this->measureDatabaseQueries(
            fn () => $query->get(),
        );

        /*
        * StorefrontFilterOptionsQuery intentionally computes its
        * publication-aware TTL before cache lookup.
        *
        * Therefore a warm cache hit performs exactly one bounded
        * query to determine the next scheduled publication, but
        * does not rebuild brands/categories/attributes/pricing.
        */
        $this->assertSame(
            1,
            $measurement['count'],
            sprintf(
                'Warm filter-options cache executed %d database queries; expected only the single publication-TTL query.',
                $measurement['count'],
            ),
        );
    }

    private function measureColdFilterQueries(
        ?Category $category = null,
        bool $includeCategories = true,
    ): int {
        /*
         * Move to a new cache generation before measurement.
         * Invalidation itself is intentionally excluded from the
         * filter-option SQL budget.
         */
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $measurement = $this->measureDatabaseQueries(
            fn () => app(
                StorefrontFilterOptionsQuery::class,
            )->get(
                $category,
                $includeCategories,
            ),
        );

        return $measurement['count'];
    }

    private function createPublishedProductWithBrand(
        ?Category $category = null,
    ): Product {
        $brand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $product = Product::factory()
            ->for($brand)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        if ($category !== null) {
            $product->categories()->attach(
                $category->id,
            );
        }

        return $product;
    }

    private function attachAttributeValue(
        ProductVariant $variant,
    ): void {
        $attribute = ProductAttribute::factory()->create([
            'is_active' => true,
        ]);

        $value = AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'is_active' => true,
            ]);

        $variant->attributeValues()->attach(
            $value->id,
        );
    }
}
