<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use Tests\TestCase;

final class PathaoProviderRegistrationTest extends TestCase
{
    public function test_pathao_is_not_registered_while_disabled(): void
    {
        config()->set(
            'shipping.providers.pathao.enabled',
            false,
        );

        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $registry = app(
            ShippingProviderRegistry::class,
        );

        $this->assertFalse(
            $registry->has(
                PathaoShippingProvider::CODE,
            ),
        );
    }

    public function test_pathao_is_registered_when_enabled(): void
    {
        config()->set(
            'shipping.providers.pathao.enabled',
            true,
        );

        config()->set(
            'shipping.providers.pathao.base_url',
            'https://pathao.test',
        );

        config()->set(
            'shipping.providers.pathao.access_token',
            'test-token',
        );

        config()->set(
            'shipping.providers.pathao.store_id',
            12345,
        );

        app()->forgetInstance(
            PathaoShippingProvider::class,
        );

        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $registry = app(
            ShippingProviderRegistry::class,
        );

        $this->assertTrue(
            $registry->has(
                PathaoShippingProvider::CODE,
            ),
        );

        $this->assertInstanceOf(
            PathaoShippingProvider::class,
            $registry->resolve(
                PathaoShippingProvider::CODE,
            ),
        );
    }
}
