<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Cache;

use App\Support\Cache\StorefrontCatalogCacheKey;
use PHPUnit\Framework\TestCase;

final class StorefrontCatalogCacheKeyTest extends TestCase
{
    public function test_product_key_is_deterministic(): void
    {
        $first = StorefrontCatalogCacheKey::product(
            'iphone-17-pro',
        );

        $second = StorefrontCatalogCacheKey::product(
            'iphone-17-pro',
        );

        $this->assertSame($first, $second);

        $this->assertStringStartsWith(
            'product:',
            $first,
        );
    }

    public function test_different_products_have_different_keys(): void
    {
        $this->assertNotSame(
            StorefrontCatalogCacheKey::product(
                'product-one',
            ),
            StorefrontCatalogCacheKey::product(
                'product-two',
            ),
        );
    }

    public function test_filter_option_keys_include_the_category_context(): void
    {
        $this->assertNotSame(
            StorefrontCatalogCacheKey::filterOptions(
                'phones',
            ),
            StorefrontCatalogCacheKey::filterOptions(
                'laptops',
            ),
        );
    }

    public function test_filter_option_keys_include_the_category_visibility_mode(): void
    {
        $this->assertNotSame(
            StorefrontCatalogCacheKey::filterOptions(
                'phones',
                true,
            ),
            StorefrontCatalogCacheKey::filterOptions(
                'phones',
                false,
            ),
        );
    }

    public function test_global_filter_option_key_is_deterministic(): void
    {
        $this->assertSame(
            StorefrontCatalogCacheKey::filterOptions(),
            StorefrontCatalogCacheKey::filterOptions(),
        );
    }
}
