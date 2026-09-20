<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontRouteQuerySecurityTest extends TestCase
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

    public function test_valid_sort_values_are_accepted(): void
    {
        foreach ([
            'newest',
            'price_asc',
            'price_desc',
            'name_asc',
            'name_desc',
        ] as $sort) {
            $response = $this->get(
                '/products?sort='.$sort,
            );

            $response->assertOk();

            $response->assertInertia(
                fn ($page) => $page
                    ->where('filters.sort', $sort),
            );
        }
    }

    public function test_unknown_sort_value_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?sort=DROP%20TABLE%20products');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('sort');
    }

    public function test_sort_array_is_rejected_as_invalid_input(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?sort[]=newest');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('sort');
    }

    public function test_brand_array_is_rejected_as_invalid_input(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?brand[]=apple');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('brand');
    }

    public function test_category_array_is_rejected_as_invalid_input(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?category[]=phones');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('category');
    }

    public function test_negative_minimum_price_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?min_price=-1');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('min_price');
    }

    public function test_negative_maximum_price_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?max_price=-1');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('max_price');
    }

    public function test_decimal_price_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?min_price=10.50');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('min_price');
    }

    public function test_non_numeric_price_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get('/products?min_price=not-a-price');

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('min_price');
    }

    public function test_maximum_price_less_than_minimum_price_is_rejected(): void
    {
        $response = $this
            ->from('/products')
            ->get(
                '/products?min_price=20000&max_price=10000',
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('max_price');
    }

    public function test_more_than_twenty_valid_attribute_filters_are_rejected(): void
    {
        $query = [];

        for ($index = 1; $index <= 21; $index++) {
            $query[
                sprintf('attributes[attribute-%d]', $index)
            ] = sprintf(
                'value-%d',
                $index,
            );
        }

        $response = $this
            ->from('/products')
            ->get(
                '/products?'.http_build_query($query),
            );

        $response
            ->assertRedirect('/products')
            ->assertSessionHasErrors('attributes');
    }

    public function test_invalid_attribute_keys_are_removed_before_reaching_public_filters(): void
    {
        $response = $this->get(
            '/products?'.http_build_query([
                'attributes' => [
                    'valid-color' => 'black',
                    'INVALID KEY!' => 'private-value',
                    'Color_UPPERCASE' => 'private-value',
                    str_repeat('a', 151) => 'private-value',
                ],
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.attributes',
                    [
                        'valid-color' => 'black',
                    ],
                ),
        );
    }

    public function test_empty_attribute_values_are_removed_before_reaching_query_layer(): void
    {
        $response = $this->get(
            '/products?'.http_build_query([
                'attributes' => [
                    'color' => '   ',
                    'size' => '',
                ],
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.attributes',
                    [],
                ),
        );
    }

    public function test_attribute_values_are_trimmed(): void
    {
        $response = $this->get(
            '/products?'.http_build_query([
                'attributes' => [
                    'color' => '  black  ',
                ],
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.attributes',
                    [
                        'color' => 'black',
                    ],
                ),
        );
    }

    public function test_attribute_value_array_is_removed_before_query_layer(): void
    {
        $response = $this->get(
            '/products?'.http_build_query([
                'attributes' => [
                    'color' => [
                        'black',
                        'red',
                    ],
                ],
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.attributes',
                    [],
                ),
        );
    }

    public function test_unknown_but_well_formed_brand_slug_is_safe(): void
    {
        $this->createPublishedProduct();

        $response = $this->get(
            '/products?brand=this-brand-does-not-exist',
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.brand',
                    'this-brand-does-not-exist',
                )
                ->where(
                    'products.data',
                    [],
                ),
        );
    }

    public function test_unknown_but_well_formed_category_slug_is_safe(): void
    {
        $this->createPublishedProduct();

        $response = $this->get(
            '/products?category=this-category-does-not-exist',
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.category',
                    'this-category-does-not-exist',
                )
                ->where(
                    'products.data',
                    [],
                ),
        );
    }

    public function test_unknown_attribute_slug_is_safe_and_returns_no_products(): void
    {
        $this->createPublishedProduct();

        $response = $this->get(
            '/products?'.http_build_query([
                'attributes' => [
                    'does-not-exist' => 'black',
                ],
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.attributes',
                    [
                        'does-not-exist' => 'black',
                    ],
                )
                ->where(
                    'products.data',
                    [],
                ),
        );
    }

    public function test_sql_like_brand_input_cannot_change_query_structure(): void
    {
        $this->createPublishedProduct();

        $attack = "' OR 1=1 --";

        $response = $this->get(
            '/products?'.http_build_query([
                'brand' => $attack,
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.brand',
                    $attack,
                )
                ->where(
                    'products.data',
                    [],
                ),
        );
    }

    public function test_sql_like_category_input_cannot_change_query_structure(): void
    {
        $this->createPublishedProduct();

        $attack = "' OR 1=1 --";

        $response = $this->get(
            '/products?'.http_build_query([
                'category' => $attack,
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.category',
                    $attack,
                )
                ->where(
                    'products.data',
                    [],
                ),
        );
    }

    public function test_unknown_product_slug_returns_normal_404(): void
    {
        $this->get(
            '/products/product-does-not-exist',
        )->assertNotFound();
    }

    public function test_sql_like_product_slug_returns_normal_404(): void
    {
        $slug = rawurlencode(
            "' OR 1=1 --",
        );

        $this->get(
            '/products/'.$slug,
        )->assertNotFound();
    }

    public function test_unknown_category_route_slug_returns_normal_404(): void
    {
        $this->get(
            '/category/category-does-not-exist',
        )->assertNotFound();
    }

    public function test_sql_like_category_route_slug_returns_normal_404(): void
    {
        $slug = rawurlencode(
            "' OR 1=1 --",
        );

        $this->get(
            '/category/'.$slug,
        )->assertNotFound();
    }

    public function test_query_string_cannot_change_product_route_resolution(): void
    {
        $product = $this->createPublishedProduct();

        $response = $this->get(
            '/products/'.$product->slug
            .'?slug=another-product'
            .'&status=draft'
            .'&published_at=2099-01-01',
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'product.id',
                    $product->id,
                )
                ->where(
                    'product.slug',
                    $product->slug,
                ),
        );
    }

    public function test_filter_strings_are_trimmed_before_becoming_filter_data(): void
    {
        $response = $this->get(
            '/products?'.http_build_query([
                'sort' => '  newest  ',
                'brand' => '  apple  ',
                'category' => '  phones  ',
            ]),
        );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->where(
                    'filters.sort',
                    'newest',
                )
                ->where(
                    'filters.brand',
                    'apple',
                )
                ->where(
                    'filters.category',
                    'phones',
                ),
        );
    }

    private function createPublishedProduct(): Product
    {
        return Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
