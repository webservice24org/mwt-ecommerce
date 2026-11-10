<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Providers\SteadfastShippingProvider;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SteadfastProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_steadfast_is_not_registered_without_an_operational_account(): void
    {
        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $registry =
            app(
                ShippingProviderRegistry::class,
            );

        $this->assertFalse(
            $registry->has(
                SteadfastShippingProvider::CODE,
            ),
        );
    }

    public function test_connected_active_steadfast_account_is_registered(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $manager =
            app(
                ShippingProviderAccountManager::class,
            );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Steadfast,

                name: 'Main Steadfast',

                credentials: [
                    'api_key' => 'STEADFAST-API-KEY',

                    'secret_key' => 'STEADFAST-SECRET-KEY',
                ],

                settings: [],

                actor: $admin,
            );

        $account =
            $manager->markConnected(
                $account,
                $admin,
            );

        $manager
            ->updateOperationalSettings(
                account: $account,

                isActive: true,

                autoSubmit: false,

                isDefault: false,

                actor: $admin,
            );

        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $registry =
            app(
                ShippingProviderRegistry::class,
            );

        $this->assertTrue(
            $registry->has(
                SteadfastShippingProvider::CODE,
            ),
        );

        $this->assertInstanceOf(
            SteadfastShippingProvider::class,
            $registry->resolve(
                SteadfastShippingProvider::CODE,
            ),
        );
    }
}
