<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\CanonicalUrlResolver;
use PHPUnit\Framework\TestCase;

final class TechnicalSeoCanonicalRegressionTest extends TestCase
{
    private CanonicalUrlResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new CanonicalUrlResolver;
    }

    public function test_canonical_host_is_enforced_for_normal_storefront_paths(): void
    {
        self::assertSame(
            'https://shop.example/products/usb-cable',
            $this->resolver->resolve(
                canonicalOverride: null,
                path: '/products/usb-cable',
                canonicalHost: 'shop.example',
            ),
        );
    }

    public function test_unsafe_canonical_override_falls_back_to_authoritative_storefront_url(): void
    {
        self::assertSame(
            'https://shop.example/products/usb-cable',
            $this->resolver->resolve(
                canonicalOverride: 'javascript:alert(1)',
                path: '/products/usb-cable',
                canonicalHost: 'https://shop.example',
            ),
        );

        self::assertSame(
            'https://shop.example/products/usb-cable',
            $this->resolver->resolve(
                canonicalOverride: 'data:text/html,unsafe',
                path: '/products/usb-cable',
                canonicalHost: 'https://shop.example',
            ),
        );
    }

    public function test_fragments_are_not_part_of_the_canonical_url(): void
    {
        self::assertSame(
            'https://shop.example/products/usb-cable',
            $this->resolver->resolve(
                canonicalOverride: null,
                path: '/products/usb-cable#reviews',
                canonicalHost: 'https://shop.example/',
            ),
        );
    }
}
