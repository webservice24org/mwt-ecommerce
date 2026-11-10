<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingProviderOperationalSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_connected_provider_operational_settings_can_be_updated_without_resetting_connection(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);

        $manager = app(
            ShippingProviderAccountManager::class,
        );

        $account = $manager->configure(
            providerCode: ShippingProviderCode::Pathao,
            name: 'Main Pathao',
            credentials: [
                'access_token' => 'TEST-TOKEN',
            ],
            settings: [
                'store_id' => 12345,
            ],
            actor: $admin,
        );

        $account = $manager->markConnected(
            $account,
            $admin,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.shipping.providers.operational-settings',
                    'pathao',
                ),
                [
                    'is_active' => true,
                    'is_default' => true,
                    'auto_submit' => true,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
            )
            ->assertRedirect(
                route(
                    'admin.shipping.providers.edit',
                    'pathao',
                ),
            );

        $account->refresh();

        $this->assertSame(
            ShippingProviderConnectionStatus::Connected,
            $account->connection_status,
        );

        $this->assertTrue(
            $account->is_active,
        );

        $this->assertTrue(
            $account->is_default,
        );

        $this->assertTrue(
            $account->auto_submit,
        );
    }

    public function test_unverified_provider_cannot_be_activated(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);

        $account = app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Steadfast,
            name: 'Main Steadfast',
            credentials: [
                'api_key' => 'TEST-API-KEY',
                'secret_key' => 'TEST-SECRET-KEY',
            ],
            settings: [],
            actor: $admin,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.shipping.providers.operational-settings',
                    'steadfast',
                ),
                [
                    'is_active' => true,
                    'is_default' => false,
                    'auto_submit' => false,
                ],
            )
            ->assertSessionHasErrors(
                'is_active',
            );

        $account->refresh();

        $this->assertSame(
            ShippingProviderConnectionStatus::Configured,
            $account->connection_status,
        );

        $this->assertFalse(
            $account->is_active,
        );
    }
}
