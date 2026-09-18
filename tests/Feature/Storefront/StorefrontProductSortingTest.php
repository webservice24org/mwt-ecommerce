<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontProductSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_sorted_by_name_ascending(): void
    {
        $this->createProduct(
            name: 'Zulu',
            slug: 'zulu',
            price: 10_000,
        );

        $this->createProduct(
            name: 'Alpha',
            slug: 'alpha',
            price: 20_000,
        );

        $this->get('/products?sort=name_asc')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'alpha',
                    )
                    ->where(
                        'products.data.1.slug',
                        'zulu',
                    ),
            );
    }

    public function test_products_can_be_sorted_by_name_descending(): void
    {
        $this->createProduct(
            name: 'Alpha',
            slug: 'alpha',
            price: 10_000,
        );

        $this->createProduct(
            name: 'Zulu',
            slug: 'zulu',
            price: 20_000,
        );

        $this->get('/products?sort=name_desc')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'zulu',
                    )
                    ->where(
                        'products.data.1.slug',
                        'alpha',
                    ),
            );
    }

    public function test_simple_products_can_be_sorted_by_price_ascending(): void
    {
        $this->createProduct(
            name: 'Expensive',
            slug: 'expensive',
            price: 30_000,
        );

        $this->createProduct(
            name: 'Cheap',
            slug: 'cheap',
            price: 10_000,
        );

        $this->get('/products?sort=price_asc')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'cheap',
                    )
                    ->where(
                        'products.data.1.slug',
                        'expensive',
                    ),
            );
    }

    public function test_simple_products_can_be_sorted_by_price_descending(): void
    {
        $this->createProduct(
            name: 'Cheap',
            slug: 'cheap',
            price: 10_000,
        );

        $this->createProduct(
            name: 'Expensive',
            slug: 'expensive',
            price: 30_000,
        );

        $this->get('/products?sort=price_desc')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'expensive',
                    )
                    ->where(
                        'products.data.1.slug',
                        'cheap',
                    ),
            );
    }

    public function test_variable_product_sort_uses_effective_variant_price(): void
    {
        $this->createProduct(
            name: 'Simple',
            slug: 'simple',
            price: 20_000,
        );

        $variable = Product::factory()
            ->variable()
            ->create([
                'name' => 'Variable',
                'slug' => 'variable',
                'price' => 50_000,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);

        ProductVariant::factory()->create([
            'product_id' => $variable->id,
            'sku' => 'SORT-VARIANT-001',
            'price' => 10_000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->get('/products?sort=price_asc')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'variable',
                    )
                    ->where(
                        'products.data.1.slug',
                        'simple',
                    ),
            );
    }

    public function test_newest_is_default_sort(): void
    {
        Product::factory()->create([
            'name' => 'Older',
            'slug' => 'older',
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(2),
        ]);

        Product::factory()->create([
            'name' => 'Newer',
            'slug' => 'newer',
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'products.data.0.slug',
                        'newer',
                    )
                    ->where(
                        'products.data.1.slug',
                        'older',
                    )
                    ->where(
                        'filters.sort',
                        'newest',
                    ),
            );
    }

    private function createProduct(
        string $name,
        string $slug,
        int $price,
    ): Product {
        return Product::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
