<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SteadfastMerchantAccountConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_connection_marks_steadfast_connected_and_active(): void
    {
        config()->set(
            'shipping.providers.steadfast.base_url',
            'https://steadfast.test/api/v1',
        );

        Http::fake([
            'https://steadfast.test/api/v1/get_balance' => Http::response(
                [
                    'status' => 200,

                    'current_balance' => -10,
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
                providerCode: ShippingProviderCode::Steadfast,

                name: 'Main Steadfast',

                credentials: [
                    'api_key' => 'STEADFAST-API-KEY',

                    'secret_key' => 'STEADFAST-SECRET-KEY',
                ],

                settings: [],

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
                    'steadfast',
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
                'https://steadfast.test/api/v1/get_balance'
                &&
                $request->hasHeader(
                    'Api-Key',
                    'STEADFAST-API-KEY',
                )
                &&
                $request->hasHeader(
                    'Secret-Key',
                    'STEADFAST-SECRET-KEY',
                ),
        );
    }

    public function test_failed_connection_marks_steadfast_error(): void
    {
        config()->set(
            'shipping.providers.steadfast.base_url',
            'https://steadfast.test/api/v1',
        );

        Http::fake([
            'https://steadfast.test/api/v1/get_balance' => Http::response(
                [
                    'status' => 401,
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
                providerCode: ShippingProviderCode::Steadfast,

                name: 'Steadfast',

                credentials: [
                    'api_key' => 'INVALID',

                    'secret_key' => 'INVALID',
                ],

                settings: [],

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
                    'steadfast',
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
}
