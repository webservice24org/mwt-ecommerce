<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\CanonicalUrlResolver;
use PHPUnit\Framework\TestCase;

final class CanonicalUrlResolverTest extends TestCase
{
    private CanonicalUrlResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver =
            new CanonicalUrlResolver;
    }

    public function test_absolute_override_wins_over_host_and_path(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'https://canonical.example.com/special-product',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://canonical.example.com/special-product',
            $resolved,
        );
    }

    public function test_relative_override_uses_canonical_host(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: '/special/product',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/special/product',
            $resolved,
        );
    }

    public function test_normal_path_uses_canonical_host_when_override_is_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_blank_override_is_treated_as_missing(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: '   ',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_host_without_scheme_defaults_to_https(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: 'shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_host_trailing_slash_is_removed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com/',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_path_repeated_slashes_are_collapsed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '//products///product-a//',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_normal_route_trailing_slash_is_removed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a/',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_root_resolves_without_trailing_slash(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/',

                canonicalHost: 'https://shop.example.com/',
            );

        $this->assertSame(
            'https://shop.example.com',
            $resolved,
        );
    }

    public function test_fragment_is_removed_from_path(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a#reviews',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_absolute_override_preserves_query_string(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'https://example.com/products/product-a?variant=blue',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://example.com/products/product-a?variant=blue',
            $resolved,
        );
    }

    public function test_path_is_returned_when_canonical_host_is_unavailable(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: null,
            );

        $this->assertSame(
            '/products/product-a',
            $resolved,
        );
    }

    public function test_javascript_override_is_not_used_as_absolute_canonical(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'javascript:alert(1)',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_data_scheme_override_is_not_used_as_absolute_canonical(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'data:text/html,test',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://shop.example.com/products/product-a',
            $resolved,
        );
    }

    public function test_absolute_http_override_is_allowed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'http://example.com/product-a',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'http://example.com/product-a',
            $resolved,
        );
    }

    public function test_absolute_https_override_is_allowed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'https://example.com/product-a',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://example.com/product-a',
            $resolved,
        );
    }

    public function test_fragment_is_removed_from_absolute_override(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: 'https://example.com/product-a#reviews',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://example.com/product-a',
            $resolved,
        );
    }

    public function test_host_path_is_preserved_when_building_canonical(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: 'https://example.com/store/',
            );

        $this->assertSame(
            'https://example.com/store/products/product-a',
            $resolved,
        );
    }

    public function test_host_query_string_is_not_used_as_canonical_base(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: null,

                path: '/products/product-a',

                canonicalHost: 'https://example.com?preview=1',
            );

        $this->assertSame(
            'https://example.com/products/product-a',
            $resolved,
        );
    }

    public function test_override_whitespace_is_trimmed(): void
    {
        $resolved =
            $this->resolver->resolve(
                canonicalOverride: '  https://example.com/product-a  ',

                path: '/products/product-a',

                canonicalHost: 'https://shop.example.com',
            );

        $this->assertSame(
            'https://example.com/product-a',
            $resolved,
        );
    }
}
