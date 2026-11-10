<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Services\PathaoShippingProviderFactory;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class TestPathaoProviderAccountConnectionAction
{
    public function __construct(
        private PathaoShippingProviderFactory $providers,

        private ShippingProviderAccountManager $accounts,
    ) {}

    public function execute(
        ShippingProviderAccount $account,
        Admin $actor,
    ): ShippingProviderAccount {
        if (
            $account->provider_code !==
            ShippingProviderCode::Pathao
        ) {
            throw ValidationException::withMessages([
                'provider' => 'The selected courier account is not Pathao.',
            ]);
        }

        try {
            $provider =
                $this->providers
                    ->fromAccount(
                        $account,
                    );

            $stores =
                $provider
                    ->testConnection();

            $storeId =
                $this->configuredStoreId(
                    $account,
                );

            if ($storeId === null) {
                throw new \RuntimeException(
                    'Pathao Store ID is not configured.',
                );
            }

            $configuredStore =
                null;

            foreach ($stores as $store) {
                if (
                    $store[
                        'store_id'
                    ] ===
                    $storeId
                ) {
                    $configuredStore =
                        $store;

                    break;
                }
            }

            if (
                ! is_array(
                    $configuredStore,
                )
            ) {
                throw new \RuntimeException(
                    'Configured Pathao store was not found.',
                );
            }

            if (
                $configuredStore[
                    'is_active'
                ] !== true
            ) {
                throw new \RuntimeException(
                    'Configured Pathao store is inactive.',
                );
            }

            $account =
                $this->accounts
                    ->markConnected(
                        $account,
                        $actor,
                    );

            /*
             * A successful explicit connection test makes
             * Pathao immediately available for manual
             * courier selection in 8.8C.
             *
             * Auto-submit remains unchanged/disabled.
             */
            return $this->accounts
                ->updateOperationalSettings(
                    account: $account,

                    isActive: true,

                    autoSubmit: $account
                        ->auto_submit,

                    isDefault: $account
                        ->is_default,

                    actor: $actor,
                );
        } catch (Throwable $exception) {
            $fresh =
                $account->fresh();

            if (
                $fresh instanceof ShippingProviderAccount
            ) {
                $this->accounts
                    ->markConnectionError(
                        account: $fresh,

                        message: 'Pathao connection test failed. Verify the credentials and configured Store ID.',

                        actor: $actor,
                    );
            }

            /*
             * Never leak provider response bodies,
             * credentials, endpoint diagnostics, or
             * exception text into the admin session.
             */
            throw ValidationException::withMessages([
                'provider' => 'Pathao connection test failed. Verify your credentials, Access Token, and Store ID.',
            ]);
        }
    }

    private function configuredStoreId(
        ShippingProviderAccount $account,
    ): ?int {
        $settings =
            $account->settings ??
            [];

        $value =
            $settings[
                'store_id'
            ] ?? null;

        if (
            is_int($value) &&
            $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            &&
            ctype_digit(
                $value,
            )
            &&
            (int) $value > 0
        ) {
            return (int) $value;
        }

        return null;
    }
}
