<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontPaginationAbuseBoundaryTest extends TestCase
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

    public function test_shop_page_is_server_bounded_to_twenty_four_products(): void
    {
        $this->createPublishedProducts(60);

        $response = $this->get('/products');

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 24)
                ->where('products.per_page', 24)
                ->where('products.current_page', 1)
                ->where('products.total', 60),
        );
    }

    public function test_second_shop_page_remains_bounded_to_twenty_four_products(): void
    {
        $this->createPublishedProducts(60);

        $response = $this->get('/products?page=2');

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 24)
                ->where('products.per_page', 24)
                ->where('products.current_page', 2)
                ->where('products.total', 60),
        );
    }

    public function test_final_shop_page_contains_only_remaining_products(): void
    {
        $this->createPublishedProducts(60);

        $response = $this->get('/products?page=3');

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 12)
                ->where('products.per_page', 24)
                ->where('products.current_page', 3)
                ->where('products.total', 60),
        );
    }

    public function test_client_cannot_override_shop_page_size(): void
    {
        $this->createPublishedProducts(60);

        foreach ([
            'per_page=1000',
            'limit=1000',
            'page_size=1000',
            'size=1000',
        ] as $parameter) {
            $response = $this->get(
                '/products?'.$parameter,
            );

            $response->assertOk();

            $response->assertInertia(
                fn ($page) => $page
                    ->has('products.data', 24)
                    ->where('products.per_page', 24)
                    ->where('products.total', 60),
            );
        }
    }

    public function test_category_page_is_server_bounded_to_twenty_four_products(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $products = $this->createPublishedProducts(60);

        $category->products()->attach(
            $products->pluck('id')->all(),
        );

        $response = $this->get(
            '/category/'.$category->slug,
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 24)
                ->where('products.per_page', 24)
                ->where('products.total', 60),
        );
    }

    public function test_client_cannot_override_category_page_size(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $products = $this->createPublishedProducts(60);

        $category->products()->attach(
            $products->pluck('id')->all(),
        );

        $response = $this->get(
            '/category/'.$category->slug.'?per_page=1000',
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 24)
                ->where('products.per_page', 24)
                ->where('products.total', 60),
        );
    }

    public function test_page_zero_is_rejected(): void
    {
        $this
            ->from('/products')
            ->get('/products?page=0')
            ->assertRedirect('/products')
            ->assertSessionHasErrors('page');
    }

    public function test_negative_page_is_rejected(): void
    {
        $this
            ->from('/products')
            ->get('/products?page=-1')
            ->assertRedirect('/products')
            ->assertSessionHasErrors('page');
    }

    public function test_decimal_page_is_rejected(): void
    {
        $this
            ->from('/products')
            ->get('/products?page=1.5')
            ->assertRedirect('/products')
            ->assertSessionHasErrors('page');
    }

    public function test_non_numeric_page_is_rejected(): void
    {
        $this
            ->from('/products')
            ->get('/products?page=attack')
            ->assertRedirect('/products')
            ->assertSessionHasErrors('page');
    }

    public function test_array_page_is_rejected_without_exception(): void
    {
        $this
            ->from('/products')
            ->get('/products?page[]=1')
            ->assertRedirect('/products')
            ->assertSessionHasErrors('page');
    }

    public function test_large_but_valid_page_returns_bounded_empty_result(): void
    {
        $this->createPublishedProducts(30);

        $response = $this->get(
            '/products?page=1000000',
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 0)
                ->where('products.per_page', 24)
                ->where('products.current_page', 1000000)
                ->where('products.total', 30),
        );
    }

    public function test_unknown_query_parameters_do_not_change_result_size(): void
    {
        $this->createPublishedProducts(60);

        $response = $this->get(
            '/products?'.http_build_query([
                'take' => 100000,
                'offset' => 0,
                'columns' => '*',
                'include' => 'variants,images,brand',
                'with' => 'variants',
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->has('products.data', 24)
                ->where('products.per_page', 24)
                ->where('products.total', 60),
        );
    }

    public function test_attribute_filter_count_above_twenty_is_rejected(): void
    {
        $attributes = [];

        for ($index = 1; $index <= 21; $index++) {
            $attributes[
                'attribute-'.$index
            ] = 'value-'.$index;
        }

        $response = $this
            ->from('/products')
            ->get(
                '/products?'.http_build_query([
                    'attributes' => $attributes,
                ]),
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('attributes');
    }

    public function test_attribute_value_longer_than_limit_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?'.http_build_query([
                    'attributes' => [
                        'color' => str_repeat('a', 181),
                    ],
                ]),
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('attributes.color');
    }

    public function test_brand_longer_than_limit_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?'.http_build_query([
                    'brand' => str_repeat('a', 181),
                ]),
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('brand');
    }

    public function test_category_longer_than_limit_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?'.http_build_query([
                    'category' => str_repeat('a', 181),
                ]),
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('category');
    }

    public function test_extremely_large_minimum_price_is_rejected_without_overflow(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?min_price=999999999999999999999999999999999999',
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('min_price');
    }

    public function test_extremely_large_maximum_price_is_rejected_without_overflow(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?max_price=999999999999999999999999999999999999',
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('max_price');
    }

    /**
     * @return Collection<int, Product>
     */
    private function createPublishedProducts(
        int $count,
    ): Collection {
        return Product::factory()
            ->count($count)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
            ]);
    }
}
