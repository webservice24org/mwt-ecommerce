<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Domain\Shipping\Services\ShippingProviderAccountResolver;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use App\Policies\ShippingProviderAccountPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ShippingProviderAccountFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_capability_contract_is_defined(): void
    {
        $this->assertFalse(
            ShippingProviderCode::Manual
                ->requiresAccount(),
        );

        $this->assertFalse(
            ShippingProviderCode::Manual
                ->supportsSubmission(),
        );

        $this->assertTrue(
            ShippingProviderCode::Pathao
                ->requiresAccount(),
        );

        $this->assertTrue(
            ShippingProviderCode::Pathao
                ->supportsSubmission(),
        );

        $this->assertTrue(
            ShippingProviderCode::Pathao
                ->supportsTrackingSync(),
        );

        $this->assertTrue(
            ShippingProviderCode::Steadfast
                ->requiresAccount(),
        );

        $this->assertTrue(
            ShippingProviderCode::Steadfast
                ->supportsSubmission(),
        );
    }

    public function test_credentials_are_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $account = app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Pathao,

            name: 'Main Pathao Account',

            credentials: [
                'client_id' => 'PATHAO-CLIENT-ID',

                'client_secret' => 'TOP-SECRET-PATHAO-CLIENT-SECRET',

                'username' => 'merchant@example.test',

                'password' => 'TOP-SECRET-PASSWORD',
            ],

            settings: [
                'store_id' => 12345,
            ],

            actor: $admin,
        );

        $rawCredentials =
            DB::table(
                'shipping_provider_accounts',
            )
                ->where(
                    'id',
                    $account->id,
                )
                ->value(
                    'credentials',
                );

        $this->assertIsString(
            $rawCredentials,
        );

        $this->assertStringNotContainsString(
            'TOP-SECRET-PATHAO-CLIENT-SECRET',
            $rawCredentials,
        );

        $this->assertStringNotContainsString(
            'TOP-SECRET-PASSWORD',
            $rawCredentials,
        );

        $fresh =
            $account->fresh();

        $this->assertInstanceOf(
            ShippingProviderAccount::class,
            $fresh,
        );

        $this->assertSame(
            'TOP-SECRET-PATHAO-CLIENT-SECRET',
            $fresh
                ->credentials[
                'client_secret'
            ] ?? null,
        );

        $serialized =
            $fresh->toArray();

        $this->assertArrayNotHasKey(
            'credentials',
            $serialized,
        );
    }

    public function test_configuring_account_marks_it_configured_but_not_operational(): void
    {
        $admin =
            $this->admin(
                AdminRole::Manager,
            );

        $account = app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Pathao,

            name: 'Pathao',

            credentials: [
                'client_id' => 'client',

                'client_secret' => 'secret',
            ],

            settings: [
                'store_id' => 123,
            ],

            actor: $admin,
        );

        $this->assertSame(
            ShippingProviderConnectionStatus::Configured,
            $account
                ->connection_status,
        );

        $this->assertFalse(
            $account->is_active,
        );

        $this->assertFalse(
            $account->isOperational(),
        );

        $this->assertNull(
            app(
                ShippingProviderAccountResolver::class,
            )->connected(
                ShippingProviderCode::Pathao,
            ),
        );
    }

    public function test_connected_active_account_can_be_resolved(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $manager = app(
            ShippingProviderAccountManager::class,
        );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Pathao',

                credentials: [
                    'client_id' => 'client',

                    'client_secret' => 'secret',
                ],

                settings: [
                    'store_id' => 123,
                ],

                actor: $admin,
            );

        $account =
            $manager->markConnected(
                $account,
                $admin,
            );

        $account =
            $manager
                ->updateOperationalSettings(
                    account: $account,

                    isActive: true,

                    autoSubmit: true,

                    isDefault: true,

                    actor: $admin,
                );

        $this->assertTrue(
            $account->isOperational(),
        );

        $this->assertTrue(
            $account->auto_submit,
        );

        $this->assertTrue(
            $account->is_default,
        );

        $resolved = app(
            ShippingProviderAccountResolver::class,
        )->connected(
            ShippingProviderCode::Pathao,
        );

        $this->assertInstanceOf(
            ShippingProviderAccount::class,
            $resolved,
        );

        $this->assertSame(
            $account->id,
            $resolved->id,
        );

        $default = app(
            ShippingProviderAccountResolver::class,
        )->default();

        $this->assertInstanceOf(
            ShippingProviderAccount::class,
            $default,
        );

        $this->assertSame(
            $account->id,
            $default->id,
        );
    }

    public function test_only_one_account_can_be_default(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $manager = app(
            ShippingProviderAccountManager::class,
        );

        $pathao =
            $this->connectedAccount(
                manager: $manager,

                admin: $admin,

                provider: ShippingProviderCode::Pathao,
            );

        $steadfast =
            $this->connectedAccount(
                manager: $manager,

                admin: $admin,

                provider: ShippingProviderCode::Steadfast,
            );

        $manager
            ->updateOperationalSettings(
                account: $pathao,

                isActive: true,

                autoSubmit: false,

                isDefault: true,

                actor: $admin,
            );

        $manager
            ->updateOperationalSettings(
                account: $steadfast,

                isActive: true,

                autoSubmit: false,

                isDefault: true,

                actor: $admin,
            );

        $this->assertFalse(
            $pathao
                ->refresh()
                ->is_default,
        );

        $this->assertTrue(
            $steadfast
                ->refresh()
                ->is_default,
        );
    }

    public function test_manual_provider_cannot_be_configured_as_merchant_account(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $this->expectException(
            ValidationException::class,
        );

        app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Manual,

            name: 'Manual',

            credentials: [
                'anything' => 'anything',
            ],

            settings: [],

            actor: $admin,
        );
    }

    public function test_disconnect_removes_credentials_and_operational_flags(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $manager = app(
            ShippingProviderAccountManager::class,
        );

        $account =
            $this->connectedAccount(
                manager: $manager,

                admin: $admin,

                provider: ShippingProviderCode::Pathao,
            );

        $account =
            $manager
                ->updateOperationalSettings(
                    account: $account,

                    isActive: true,

                    autoSubmit: true,

                    isDefault: true,

                    actor: $admin,
                );

        $account =
            $manager->disconnect(
                $account,
                $admin,
            );

        $this->assertSame(
            ShippingProviderConnectionStatus::Disconnected,
            $account
                ->connection_status,
        );

        $this->assertNull(
            $account->credentials,
        );

        $this->assertFalse(
            $account->is_active,
        );

        $this->assertFalse(
            $account->is_default,
        );

        $this->assertFalse(
            $account->auto_submit,
        );
    }

    public function test_admin_and_manager_can_manage_accounts_but_editor_cannot(): void
    {
        $policy =
            new ShippingProviderAccountPolicy;

        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $manager =
            $this->admin(
                AdminRole::Manager,
            );

        $editor =
            $this->admin(
                AdminRole::Editor,
            );

        $this->assertTrue(
            $policy->viewAny(
                $admin,
            ),
        );

        $this->assertTrue(
            $policy->viewAny(
                $manager,
            ),
        );

        $this->assertFalse(
            $policy->viewAny(
                $editor,
            ),
        );

        $this->assertFalse(
            $policy->create(
                $editor,
            ),
        );
    }

    private function connectedAccount(
        ShippingProviderAccountManager $manager,
        Admin $admin,
        ShippingProviderCode $provider,
    ): ShippingProviderAccount {
        $account =
            $manager->configure(
                providerCode: $provider,

                name: $provider
                    ->label(),

                credentials: [
                    'key' => 'secret',
                ],

                settings: [],

                actor: $admin,
            );

        return $manager
            ->markConnected(
                $account,
                $admin,
            );
    }

    private function admin(
        AdminRole $role,
    ): Admin {
        return Admin::factory()
            ->create([
                'role' => $role,

                'is_active' => true,
            ]);
    }
}
