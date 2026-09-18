<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontProductFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_arbitrary_sort_value_is_rejected(): void
    {
        $this->get('/products?sort=DROP_TABLE')
            ->assertSessionHasErrors('sort');
    }

    public function test_negative_minimum_price_is_rejected(): void
    {
        $this->get('/products?min_price=-1')
            ->assertSessionHasErrors('min_price');
    }

    public function test_negative_maximum_price_is_rejected(): void
    {
        $this->get('/products?max_price=-1')
            ->assertSessionHasErrors('max_price');
    }

    public function test_maximum_price_cannot_be_lower_than_minimum_price(): void
    {
        $this->get(
            '/products?min_price=20000&max_price=10000',
        )
            ->assertSessionHasErrors('max_price');
    }

    public function test_too_many_attribute_filters_are_rejected(): void
    {
        $attributes = [];

        for ($i = 1; $i <= 21; $i++) {
            $attributes["attribute-{$i}"] = "value-{$i}";
        }

        $this->get('/products?'.http_build_query([
            'attributes' => $attributes,
        ]))
            ->assertSessionHasErrors('attributes');
    }

    public function test_overlong_brand_slug_is_rejected(): void
    {
        $this->get('/products?'.http_build_query([
            'brand' => str_repeat('a', 181),
        ]))
            ->assertSessionHasErrors('brand');
    }

    public function test_overlong_category_slug_is_rejected(): void
    {
        $this->get('/products?'.http_build_query([
            'category' => str_repeat('a', 181),
        ]))
            ->assertSessionHasErrors('category');
    }
}
