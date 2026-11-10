<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Shipping;

use App\Domain\Shipping\Actions\TestPathaoProviderAccountConnectionAction;
use App\Domain\Shipping\Actions\TestSteadfastProviderAccountConnectionAction;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Shipping\UpdateShippingProviderAccountRequest;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ShippingProviderAccountController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(
            'viewAny',
            ShippingProviderAccount::class,
        );

        $accounts =
            ShippingProviderAccount::query()
                ->get()
                ->keyBy(
                    static fn (
                        ShippingProviderAccount $account,
                    ): string => $account
                        ->provider_code
                        ->value,
                );

        return Inertia::render(
            'Admin/Shipping/Providers/Index',
            [
                'providers' => [
                    $this->manualProvider(),

                    $this->accountProvider(
                        ShippingProviderCode::Pathao,
                        $accounts->get(
                            ShippingProviderCode::Pathao
                                ->value,
                        ),
                    ),

                    $this->accountProvider(
                        ShippingProviderCode::Steadfast,
                        $accounts->get(
                            ShippingProviderCode::Steadfast
                                ->value,
                        ),
                    ),
                ],
            ],
        );
    }

    public function edit(
        string $provider,
    ): Response {
        $providerCode =
            $this->editableProvider(
                $provider,
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    $providerCode->value,
                )
                ->first();

        if ($account instanceof ShippingProviderAccount) {
            Gate::authorize(
                'update',
                $account,
            );
        } else {
            Gate::authorize(
                'create',
                ShippingProviderAccount::class,
            );
        }

        return Inertia::render(
            'Admin/Shipping/Providers/Edit',
            [
                'provider' => [
                    'code' => $providerCode->value,

                    'name' => $providerCode->label(),

                    'account' => $account
                        ? $this->editableAccount(
                            $account,
                        )
                        : null,

                    'defaults' => [
                        'delivery_type' => (int) config(
                            'shipping.providers.pathao.delivery_type',
                            48,
                        ),

                        'item_type' => (int) config(
                            'shipping.providers.pathao.item_type',
                            2,
                        ),

                        'default_item_weight_kg' => (float) config(
                            'shipping.providers.pathao.default_item_weight_kg',
                            0.5,
                        ),
                    ],
                ],
            ],
        );
    }

    public function update(
        UpdateShippingProviderAccountRequest $request,
        string $provider,
        ShippingProviderAccountManager $manager,
    ): RedirectResponse {
        $providerCode =
            $this->editableProvider(
                $provider,
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    $providerCode->value,
                )
                ->first();

        if ($account instanceof ShippingProviderAccount) {
            Gate::authorize(
                'update',
                $account,
            );
        } else {
            Gate::authorize(
                'create',
                ShippingProviderAccount::class,
            );
        }

        if (
            $providerCode ===
            ShippingProviderCode::Steadfast
        ) {
            /*
             * Steadfast uses only API Key + Secret Key.
             *
             * Blank fields retain an already-encrypted value.
             * We deliberately do not persist Pathao-only settings
             * into the Steadfast account.
             */
            $validated =
                $request->validate([
                    'name' => [
                        'required',
                        'string',
                        'max:120',
                    ],

                    'credentials' => [
                        'nullable',
                        'array',
                    ],

                    'credentials.api_key' => [
                        'nullable',
                        'string',
                        'max:500',
                    ],

                    'credentials.secret_key' => [
                        'nullable',
                        'string',
                        'max:500',
                    ],
                ]);

            $credentials =
                $account
                ? $account->credentials ?? []
                : [];

            $submittedCredentials =
                $validated[
                    'credentials'
                ] ?? [];

            if (! is_array($submittedCredentials)) {
                $submittedCredentials = [];
            }

            foreach (
                [
                    'api_key',
                    'secret_key',
                ] as $key
            ) {
                $value =
                    $submittedCredentials[
                        $key
                    ] ?? null;

                if (! is_string($value)) {
                    continue;
                }

                $value =
                    trim(
                        $value,
                    );

                if ($value !== '') {
                    $credentials[
                        $key
                    ] = $value;
                }
            }

            foreach (
                [
                    'api_key',
                    'secret_key',
                ] as $requiredKey
            ) {
                if (
                    ! is_string(
                        $credentials[
                            $requiredKey
                        ] ?? null,
                    )
                    ||
                    trim(
                        (string) (
                            $credentials[
                                $requiredKey
                            ] ?? ''
                        ),
                    ) === ''
                ) {
                    throw ValidationException::withMessages([
                        "credentials.{$requiredKey}" => 'This Steadfast credential is required.',
                    ]);
                }
            }

            $manager->configure(
                providerCode: $providerCode,

                name: trim(
                    (string) $validated[
                        'name'
                    ],
                ),

                credentials: $credentials,

                settings: [],

                actor: $this->admin(
                    $request,
                ),
            );

            return to_route(
                'admin.shipping.providers.index',
            )->with(
                'success',
                'Steadfast credentials saved. Run Test connection before using the account.',
            );
        }

        /*
         * Pathao keeps its existing credential/settings contract.
         */
        $validated =
            $request->validated();

        $credentials =
            $account
            ? $account->credentials ?? []
            : [];

        $submittedCredentials =
            $validated[
                'credentials'
            ] ?? [];

        if (! is_array($submittedCredentials)) {
            $submittedCredentials = [];
        }

        foreach (
            [
                'client_id',
                'client_secret',
                'access_token',
                'username',
                'password',
            ] as $key
        ) {
            $value =
                $submittedCredentials[
                    $key
                ] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $value =
                trim(
                    $value,
                );

            /*
             * Blank means retain the previously encrypted
             * value instead of destroying the credential.
             */
            if ($value !== '') {
                $credentials[
                    $key
                ] = $value;
            }
        }

        $settings =
            $validated[
                'settings'
            ] ?? [];

        if (! is_array($settings)) {
            $settings = [];
        }

        $manager->configure(
            providerCode: $providerCode,

            name: (string) $validated[
                'name'
            ],

            credentials: $credentials,

            settings: [
                'store_id' => (int) (
                    $settings[
                        'store_id'
                    ] ?? 0
                ),

                'delivery_type' => (int) (
                    $settings[
                        'delivery_type'
                    ] ?? 48
                ),

                'item_type' => (int) (
                    $settings[
                        'item_type'
                    ] ?? 2
                ),

                'default_item_weight_kg' => (float) (
                    $settings[
                        'default_item_weight_kg'
                    ] ?? 0.5
                ),
            ],

            actor: $this->admin(
                $request,
            ),
        );

        return to_route(
            'admin.shipping.providers.index',
        )->with(
            'success',
            'Courier account configuration saved. Test the connection before enabling it.',
        );
    }

    public function updateOperationalSettings(
        Request $request,
        string $provider,
        ShippingProviderAccountManager $manager,
    ): RedirectResponse {
        $providerCode =
            $this->editableProvider(
                $provider,
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    $providerCode->value,
                )
                ->firstOrFail();

        Gate::authorize(
            'update',
            $account,
        );

        $validated =
            $request->validate([
                'is_active' => [
                    'required',
                    'boolean',
                ],

                'is_default' => [
                    'required',
                    'boolean',
                ],

                'auto_submit' => [
                    'required',
                    'boolean',
                ],
            ]);

        $isActive =
            (bool) $validated[
                'is_active'
            ];

        $isDefault =
            (bool) $validated[
                'is_default'
            ];

        $autoSubmit =
            (bool) $validated[
                'auto_submit'
            ];

        if (
            $isActive
            &&
            ! $account->isConnected()
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'Test the courier connection successfully before activating this provider.',
            ]);
        }

        if (
            $isDefault
            &&
            ! $isActive
        ) {
            throw ValidationException::withMessages([
                'is_default' => 'The default courier provider must also be active.',
            ]);
        }

        if (
            $autoSubmit
            &&
            ! $isActive
        ) {
            throw ValidationException::withMessages([
                'auto_submit' => 'Automatic submission requires an active courier provider.',
            ]);
        }

        $manager->updateOperationalSettings(
            account: $account,

            isActive: $isActive,

            autoSubmit: $autoSubmit,

            isDefault: $isDefault,

            actor: $this->admin(
                $request,
            ),
        );

        return to_route(
            'admin.shipping.providers.edit',
            $providerCode->value,
        )->with(
            'success',
            'Courier operational settings updated.',
        );
    }

    public function disconnect(
        Request $request,
        string $provider,
        ShippingProviderAccountManager $manager,
    ): RedirectResponse {
        $providerCode =
            $this->editableProvider(
                $provider,
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    $providerCode->value,
                )
                ->firstOrFail();

        Gate::authorize(
            'delete',
            $account,
        );

        $manager->disconnect(
            $account,
            $this->admin(
                $request,
            ),
        );

        return to_route(
            'admin.shipping.providers.index',
        )->with(
            'success',
            'Courier account disconnected and stored credentials removed.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function manualProvider(): array
    {
        return [
            'code' => ShippingProviderCode::Manual
                ->value,

            'name' => ShippingProviderCode::Manual
                ->label(),

            'external' => false,

            'available' => true,

            'implemented' => true,

            'account' => null,

            'edit_url' => null,

            'test_url' => null,

            'disconnect_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountProvider(
        ShippingProviderCode $provider,
        ?ShippingProviderAccount $account,
    ): array {
        /*
         * Both merchant-account configuration surfaces are
         * implemented. Provider runtime submission support is
         * registered separately by each integration gate.
         */
        $implemented =
            in_array(
                $provider,
                [
                    ShippingProviderCode::Pathao,
                    ShippingProviderCode::Steadfast,
                ],
                true,
            );

        return [
            'code' => $provider->value,

            'name' => $provider->label(),

            'external' => true,

            'available' => $account?->isOperational() ??
                false,

            'implemented' => $implemented,

            'account' => $account
                ? [
                    'id' => $account->id,

                    'name' => $account->name,

                    'is_active' => $account->is_active,

                    'is_default' => $account->is_default,

                    'auto_submit' => $account->auto_submit,

                    'connection_status' => [
                        'value' => $account
                            ->connection_status
                            ->value,

                        'label' => $account
                            ->connection_status
                            ->label(),
                    ],

                    'last_tested_at' => $account
                        ->last_tested_at
                        ?->toISOString(),

                    'last_connected_at' => $account
                        ->last_connected_at
                        ?->toISOString(),

                    'last_error' => $account
                        ->last_error,

                    'credentials_configured' => is_array(
                        $account
                            ->credentials,
                    )
                        &&
                        $account
                            ->credentials !== [],
                ]
                : null,

            'edit_url' => $implemented
                ? route(
                    'admin.shipping.providers.edit',
                    $provider->value,
                )
                : null,

            'test_url' => $implemented
                && $account
                ? route(
                    'admin.shipping.providers.test',
                    $provider->value,
                )
                : null,

            'disconnect_url' => $implemented
                && $account
                ? route(
                    'admin.shipping.providers.disconnect',
                    $provider->value,
                )
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editableAccount(
        ShippingProviderAccount $account,
    ): array {
        $credentials =
            $account->credentials ??
            [];

        $settings =
            $account->settings ??
            [];

        return [
            'id' => $account->id,

            'name' => $account->name,

            'is_active' => $account->is_active,

            'is_default' => $account->is_default,

            'auto_submit' => $account->auto_submit,

            /*
             * Never return credential values.
             * Only expose whether each secret exists.
             */
            'credential_state' => [
                'client_id' => $this->hasCredential(
                    $credentials,
                    'client_id',
                ),

                'client_secret' => $this->hasCredential(
                    $credentials,
                    'client_secret',
                ),

                'access_token' => $this->hasCredential(
                    $credentials,
                    'access_token',
                ),

                'username' => $this->hasCredential(
                    $credentials,
                    'username',
                ),

                'password' => $this->hasCredential(
                    $credentials,
                    'password',
                ),

                'api_key' => $this->hasCredential(
                    $credentials,
                    'api_key',
                ),

                'secret_key' => $this->hasCredential(
                    $credentials,
                    'secret_key',
                ),
            ],

            'settings' => [
                'store_id' => $settings[
                    'store_id'
                ] ?? null,

                'delivery_type' => $settings[
                    'delivery_type'
                ] ?? null,

                'item_type' => $settings[
                    'item_type'
                ] ?? null,

                'default_item_weight_kg' => $settings[
                    'default_item_weight_kg'
                ] ?? null,
            ],

            'connection_status' => [
                'value' => $account
                ->connection_status
                ->value,

                'label' => $account
                ->connection_status
                ->label(),
            ],
        ];
    }

    private function editableProvider(
        string $provider,
    ): ShippingProviderCode {
        $providerCode =
            ShippingProviderCode::tryFrom(
                strtolower(
                    trim(
                        $provider,
                    ),
                ),
            );

        abort_unless(
            in_array(
                $providerCode,
                [
                    ShippingProviderCode::Pathao,
                    ShippingProviderCode::Steadfast,
                ],
                true,
            ),
            404,
        );

        return $providerCode;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function hasCredential(
        array $credentials,
        string $key,
    ): bool {
        $value =
            $credentials[
                $key
            ] ?? null;

        return is_string($value)
            && trim($value) !== '';
    }

    private function admin(
        Request $request,
    ): Admin {
        $admin =
            $request->user(
                'admin',
            );

        abort_unless(
            $admin instanceof Admin,
            403,
        );

        return $admin;
    }

    public function testConnection(
        Request $request,
        string $provider,
        TestPathaoProviderAccountConnectionAction $pathao,
        TestSteadfastProviderAccountConnectionAction $steadfast,
    ): RedirectResponse {
        $providerCode =
            $this->editableProvider(
                $provider,
            );

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    $providerCode
                        ->value,
                )
                ->firstOrFail();

        Gate::authorize(
            'update',
            $account,
        );

        match ($providerCode) {
            ShippingProviderCode::Pathao => $pathao->execute(
                account: $account,

                actor: $this->admin(
                    $request,
                ),
            ),

            ShippingProviderCode::Steadfast => $steadfast->execute(
                account: $account,

                actor: $this->admin(
                    $request,
                ),
            ),

            default => abort(404),
        };

        return to_route(
            'admin.shipping.providers.index',
        )->with(
            'success',
            sprintf(
                '%s connection verified. The courier account is now connected.',
                $providerCode->label(),
            ),
        );
    }
}
