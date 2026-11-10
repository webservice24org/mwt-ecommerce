<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Models\Admin;
use App\Models\ShippingProviderAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ShippingProviderAccountManager
{
    /**
     * @param  array<string, mixed>  $credentials
     * @param  array<string, mixed>  $settings
     */
    public function configure(
        ShippingProviderCode $providerCode,
        string $name,
        array $credentials,
        array $settings,
        Admin $actor,
    ): ShippingProviderAccount {
        if (
            ! $providerCode
                ->requiresAccount()
        ) {
            throw ValidationException::withMessages([
                'provider_code' => 'The manual shipping provider does not require a connected account.',
            ]);
        }

        if ($credentials === []) {
            throw ValidationException::withMessages([
                'credentials' => 'Provider credentials are required.',
            ]);
        }

        $name =
            trim(
                $name,
            );

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Provider account name is required.',
            ]);
        }

        return DB::transaction(
            function () use ($providerCode, $name, $credentials, $settings, $actor): ShippingProviderAccount {
                $account =
                    ShippingProviderAccount::query()
                        ->firstOrNew([
                            'provider_code' => $providerCode,
                        ]);

                $creating =
                    ! $account->exists;

                $account->fill([
                    'name' => $name,

                    'credentials' => $credentials,

                    'settings' => $settings,

                    /*
                     * Saving new credentials invalidates any
                     * previous connection verification.
                     */
                    'connection_status' => ShippingProviderConnectionStatus::Configured,

                    'last_tested_at' => null,

                    'last_connected_at' => null,

                    'last_error' => null,

                    'updated_by_admin_id' => $actor->id,
                ]);

                if ($creating) {
                    $account
                        ->created_by_admin_id =
                        $actor->id;
                }

                $account->save();

                return $account
                    ->refresh();
            },
        );
    }

    public function markConnected(
        ShippingProviderAccount $account,
        Admin $actor,
    ): ShippingProviderAccount {
        if (
            ! $account
                ->provider_code
                ->requiresAccount()
        ) {
            throw ValidationException::withMessages([
                'provider_code' => 'The manual shipping provider cannot have a connected merchant account.',
            ]);
        }

        $testedAt =
            now();

        $account->forceFill([
            'connection_status' => ShippingProviderConnectionStatus::Connected,

            'last_tested_at' => $testedAt,

            'last_connected_at' => $testedAt,

            'last_error' => null,

            'updated_by_admin_id' => $actor->id,
        ])->save();

        return $account
            ->refresh();
    }

    public function markConnectionError(
        ShippingProviderAccount $account,
        string $message,
        Admin $actor,
    ): ShippingProviderAccount {
        $message =
            trim(
                $message,
            );

        $account->forceFill([
            'connection_status' => ShippingProviderConnectionStatus::Error,

            'last_tested_at' => now(),

            'last_error' => $message !== ''
                ? $message
                : 'Provider connection test failed.',

            'updated_by_admin_id' => $actor->id,
        ])->save();

        return $account
            ->refresh();
    }

    public function updateOperationalSettings(
        ShippingProviderAccount $account,
        bool $isActive,
        bool $autoSubmit,
        bool $isDefault,
        Admin $actor,
    ): ShippingProviderAccount {
        if (
            (
                $isActive
                || $autoSubmit
                || $isDefault
            )
            &&
            ! $account->isConnected()
        ) {
            throw ValidationException::withMessages([
                'connection_status' => 'Test and connect the courier account before enabling it.',
            ]);
        }

        if (
            $autoSubmit
            &&
            ! $isActive
        ) {
            throw ValidationException::withMessages([
                'auto_submit' => 'Automatic submission requires an active courier account.',
            ]);
        }

        if (
            $isDefault
            &&
            ! $isActive
        ) {
            throw ValidationException::withMessages([
                'is_default' => 'The default courier account must be active.',
            ]);
        }

        return DB::transaction(
            function () use ($account, $isActive, $autoSubmit, $isDefault, $actor): ShippingProviderAccount {
                if ($isDefault) {
                    ShippingProviderAccount::query()
                        ->whereKeyNot(
                            $account->id,
                        )
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $account->forceFill([
                    'is_active' => $isActive,

                    'auto_submit' => $autoSubmit,

                    'is_default' => $isDefault,

                    'updated_by_admin_id' => $actor->id,
                ])->save();

                return $account
                    ->refresh();
            },
        );
    }

    public function disconnect(
        ShippingProviderAccount $account,
        Admin $actor,
    ): ShippingProviderAccount {
        $account->forceFill([
            /*
             * Disconnect means secrets are removed rather
             * than merely hiding a still-valid credential.
             */
            'credentials' => null,

            'is_active' => false,

            'is_default' => false,

            'auto_submit' => false,

            'connection_status' => ShippingProviderConnectionStatus::Disconnected,

            'last_tested_at' => null,

            'last_connected_at' => null,

            'last_error' => null,

            'updated_by_admin_id' => $actor->id,
        ])->save();

        return $account
            ->refresh();
    }
}
