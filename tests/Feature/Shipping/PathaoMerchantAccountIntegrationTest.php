<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PathaoMerchantAccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_pathao_connection_test_marks_account_connected_and_active(): void
    {
        config()->set(
            'shipping.providers.pathao.base_url',
            'https://pathao.test',
        );

        Http::fake([
            'https://pathao.test/aladdin/api/v1/stores' => Http::response(
                [
                    'message' => 'Store list fetched.',

                    'type' => 'success',

                    'code' => 200,

                    'data' => [
                        'data' => [
                            [
                                'store_id' => 12345,

                                'store_name' => 'Main Store',

                                'is_active' => 1,
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $account =
            app(
                ShippingProviderAccountManager::class,
            )->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Main Pathao',

                credentials: [
                    'access_token' => 'DATABASE-PATHAO-TOKEN',
                ],

                settings: [
                    'store_id' => 12345,

                    'delivery_type' => 48,

                    'item_type' => 2,

                    'default_item_weight_kg' => 0.5,
                ],

                actor: $admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.shipping.providers.test',
                    'pathao',
                ),
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.shipping.providers.index',
                ),
            );

        $account->refresh();

        $this->assertSame(
            ShippingProviderConnectionStatus::Connected,
            $account
                ->connection_status,
        );

        $this->assertTrue(
            $account
                ->is_active,
        );

        $this->assertTrue(
            $account
                ->isOperational(),
        );

        Http::assertSent(
            static fn (
                Request $request,
            ): bool => $request->url() ===
                'https://pathao.test/aladdin/api/v1/stores'
                &&
                $request->hasHeader(
                    'Authorization',
                    'Bearer DATABASE-PATHAO-TOKEN',
                ),
        );
    }

    public function test_failed_pathao_connection_test_marks_account_error(): void
    {
        config()->set(
            'shipping.providers.pathao.base_url',
            'https://pathao.test',
        );

        Http::fake([
            'https://pathao.test/aladdin/api/v1/stores' => Http::response(
                [
                    'message' => 'Unauthenticated.',
                ],
                401,
            ),
        ]);

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $account =
            app(
                ShippingProviderAccountManager::class,
            )->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Pathao',

                credentials: [
                    'access_token' => 'INVALID',
                ],

                settings: [
                    'store_id' => 12345,
                ],

                actor: $admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.shipping.providers.test',
                    'pathao',
                ),
            )
            ->assertSessionHasErrors(
                'provider',
            );

        $account->refresh();

        $this->assertSame(
            ShippingProviderConnectionStatus::Error,
            $account
                ->connection_status,
        );

        $this->assertFalse(
            $account
                ->isOperational(),
        );
    }

    public function test_configured_db_account_prevents_silent_environment_fallback(): void
    {
        config()->set(
            'shipping.providers.pathao.enabled',
            true,
        );

        config()->set(
            'shipping.providers.pathao.access_token',
            'ENV-TOKEN',
        );

        config()->set(
            'shipping.providers.pathao.store_id',
            999,
        );

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Pathao,

            name: 'DB Pathao',

            credentials: [
                'access_token' => 'DB-TOKEN',
            ],

            settings: [
                'store_id' => 12345,
            ],

            actor: $admin,
        );

        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $registry =
            app(
                ShippingProviderRegistry::class,
            );

        /*
         * DB account exists but is only Configured.
         * Environment credentials must NOT take over.
         */
        $this->assertFalse(
            $registry->has(
                PathaoShippingProvider::CODE,
            ),
        );
    }

    public function test_connected_db_account_is_registered_in_runtime_registry(): void
    {
        config()->set(
            'shipping.providers.pathao.base_url',
            'https://pathao.test',
        );

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $manager =
            app(
                ShippingProviderAccountManager::class,
            );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'DB Pathao',

                credentials: [
                    'access_token' => 'DB-TOKEN',
                ],

                settings: [
                    'store_id' => 12345,
                ],

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
