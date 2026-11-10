<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class AdminShippingProviderAccountUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_courier_provider_index(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.shipping.providers.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Admin/Shipping/Providers/Index',
                    )
                    ->has(
                        'providers',
                        3,
                    )
                    ->where(
                        'providers.0.code',
                        'manual',
                    )
                    ->where(
                        'providers.1.code',
                        'pathao',
                    )
                    ->where(
                        'providers.2.code',
                        'steadfast',
                    ),
            );
    }

    public function test_editor_cannot_view_courier_provider_accounts(): void
    {
        $editor =
            $this->admin(
                AdminRole::Editor,
            );

        $this
            ->actingAs(
                $editor,
                'admin',
            )
            ->get(
                route(
                    'admin.shipping.providers.index',
                ),
            )
            ->assertForbidden();
    }

    public function test_admin_can_save_pathao_credentials_without_exposing_them(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.shipping.providers.update',
                    ShippingProviderCode::Pathao
                        ->value,
                ),
                [
                    'name' => 'Main Pathao',

                    'credentials' => [
                        'client_id' => 'CLIENT-ID',

                        'client_secret' => 'TOP-SECRET',

                        'access_token' => '',
                    ],

                    'settings' => [
                        'store_id' => 12345,

                        'delivery_type' => 48,

                        'item_type' => 2,

                        'default_item_weight_kg' => 0.5,
                    ],
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.shipping.providers.index',
                ),
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    ShippingProviderCode::Pathao
                        ->value,
                )
                ->firstOrFail();

        $this->assertSame(
            'TOP-SECRET',
            $account
                ->credentials[
                'client_secret'
            ] ?? null,
        );

        $raw =
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
            $raw,
        );

        $this->assertStringNotContainsString(
            'TOP-SECRET',
            $raw,
        );

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.shipping.providers.edit',
                        'pathao',
                    ),
                );

        $response
            ->assertOk();

        $response
            ->assertDontSee(
                'TOP-SECRET',
            );
    }

    public function test_blank_secret_keeps_existing_encrypted_credential(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $account =
            ShippingProviderAccount::query()
                ->create([
                    'provider_code' => ShippingProviderCode::Pathao,

                    'name' => 'Pathao',

                    'credentials' => [
                        'client_id' => 'OLD-ID',

                        'client_secret' => 'OLD-SECRET',
                    ],

                    'settings' => [
                        'store_id' => 123,
                    ],

                    'created_by_admin_id' => $admin->id,

                    'updated_by_admin_id' => $admin->id,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.shipping.providers.update',
                    'pathao',
                ),
                [
                    'name' => 'Pathao Updated',

                    'credentials' => [
                        'client_id' => '',

                        'client_secret' => '',
                    ],

                    'settings' => [
                        'store_id' => 123,

                        'delivery_type' => 48,

                        'item_type' => 2,

                        'default_item_weight_kg' => 0.5,
                    ],
                ],
            )
            ->assertSessionHasNoErrors();

        $account->refresh();

        $this->assertSame(
            'OLD-ID',
            $account
                ->credentials[
                'client_id'
            ] ?? null,
        );

        $this->assertSame(
            'OLD-SECRET',
            $account
                ->credentials[
                'client_secret'
            ] ?? null,
        );
    }

    public function test_admin_can_disconnect_pathao_and_remove_credentials(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $account =
            ShippingProviderAccount::query()
                ->create([
                    'provider_code' => ShippingProviderCode::Pathao,

                    'name' => 'Pathao',

                    'credentials' => [
                        'client_secret' => 'SECRET',
                    ],

                    'settings' => [
                        'store_id' => 123,
                    ],

                    'created_by_admin_id' => $admin->id,

                    'updated_by_admin_id' => $admin->id,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.shipping.providers.disconnect',
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

        $this->assertNull(
            $account
                ->credentials,
        );

        $this->assertFalse(
            $account
                ->is_active,
        );

        $this->assertFalse(
            $account
                ->auto_submit,
        );
    }

    public function test_steadfast_configuration_is_enabled_and_credentials_are_stored_securely(): void
    {
        $admin =
            $this->admin(
                AdminRole::Admin,
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.shipping.providers.edit',
                    'steadfast',
                ),
            )
            ->assertOk();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.shipping.providers.update',
                    'steadfast',
                ),
                [
                    'name' => 'Main Steadfast',

                    'credentials' => [
                        'api_key' => 'STEADFAST-API-KEY',

                        'secret_key' => 'STEADFAST-SECRET-KEY',
                    ],
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
            )
            ->assertRedirect(
                route(
                    'admin.shipping.providers.index',
                ),
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    'steadfast',
                )
                ->firstOrFail();

        $this->assertSame(
            'Main Steadfast',
            $account->name,
        );

        $this->assertSame(
            'configured',
            $account
                ->connection_status
                ->value,
        );

        $this->assertFalse(
            $account->is_active,
        );

        $credentials =
            $account->credentials;

        $this->assertIsArray(
            $credentials,
        );

        $this->assertSame(
            'STEADFAST-API-KEY',
            $credentials['api_key'] ?? null,
        );

        $this->assertSame(
            'STEADFAST-SECRET-KEY',
            $credentials['secret_key'] ?? null,
        );

        /*
         * Encrypted secrets must never be returned by the
         * provider index Inertia payload.
         */
        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.shipping.providers.index',
                    ),
                )
                ->assertOk();

        $payload =
            $response
                ->viewData(
                    'page',
                );

        $encoded =
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'STEADFAST-API-KEY',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'STEADFAST-SECRET-KEY',
            $encoded,
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
