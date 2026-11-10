<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Models\ShippingProviderAccount;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final readonly class PathaoShippingProviderFactory
{
    public function fromAccount(
        ShippingProviderAccount $account,
    ): PathaoShippingProvider {
        if (
            $account->provider_code !==
            ShippingProviderCode::Pathao
        ) {
            throw new InvalidArgumentException(
                'The courier account is not a Pathao account.',
            );
        }

        $credentials =
            $account->credentials ?? [];

        $settings =
            $account->settings ?? [];

        return new PathaoShippingProvider(
            /*
             * Application controlled.
             * Never read API hosts from merchant input.
             */
            baseUrl: $this->configString(
                'shipping.providers.pathao.base_url',
                'https://api-hermes.pathao.com',
            ),

            staticAccessToken: $this->arrayString(
                $credentials,
                'access_token',
            ),

            clientId: $this->arrayString(
                $credentials,
                'client_id',
            ),

            clientSecret: $this->arrayString(
                $credentials,
                'client_secret',
            ),

            username: $this->arrayString(
                $credentials,
                'username',
            ),

            password: $this->arrayString(
                $credentials,
                'password',
            ),

            storeId: $this->arrayInt(
                $settings,
                'store_id',
            ) ?? 0,

            deliveryType: $this->arrayInt(
                $settings,
                'delivery_type',
            ) ?? 48,

            itemType: $this->arrayInt(
                $settings,
                'item_type',
            ) ?? 2,

            defaultItemWeightKg: $this->arrayFloat(
                $settings,
                'default_item_weight_kg',
            ) ?? 0.5,

            connectTimeoutSeconds: (int) config(
                'shipping.providers.pathao.connect_timeout_seconds',
                5,
            ),

            timeoutSeconds: (int) config(
                'shipping.providers.pathao.timeout_seconds',
                15,
            ),
        );
    }

    /**
     * Runtime provider selection.
     *
     * IMPORTANT:
     *
     * Once a DB Pathao account exists, that account is
     * authoritative. We must never silently fall back to
     * .env credentials for another merchant account.
     */
    public function runtimeProvider(
    ): ?PathaoShippingProvider {
        if (
            Schema::hasTable(
                'shipping_provider_accounts',
            )
        ) {
            $account =
                ShippingProviderAccount::query()
                    ->where(
                        'provider_code',
                        ShippingProviderCode::Pathao
                            ->value,
                    )
                    ->first();

            if (
                $account instanceof ShippingProviderAccount
            ) {
                if (
                    ! $account
                        ->isOperational()
                ) {
                    return null;
                }

                return $this
                    ->fromAccount(
                        $account,
                    );
            }
        }

        /*
         * Legacy migration fallback.
         *
         * Used only if no Pathao DB account has ever
         * been configured.
         */
        if (
            ! (bool) config(
                'shipping.providers.pathao.enabled',
                false,
            )
        ) {
            return null;
        }

        return $this
            ->fromConfiguration();
    }

    public function fromConfiguration(
    ): PathaoShippingProvider {
        return new PathaoShippingProvider(
            baseUrl: $this->configString(
                'shipping.providers.pathao.base_url',
                'https://api-hermes.pathao.com',
            ),

            staticAccessToken: $this->nullableConfigString(
                'shipping.providers.pathao.access_token',
            ),

            clientId: $this->nullableConfigString(
                'shipping.providers.pathao.client_id',
            ),

            clientSecret: $this->nullableConfigString(
                'shipping.providers.pathao.client_secret',
            ),

            username: $this->nullableConfigString(
                'shipping.providers.pathao.username',
            ),

            password: $this->nullableConfigString(
                'shipping.providers.pathao.password',
            ),

            storeId: (int) config(
                'shipping.providers.pathao.store_id',
                0,
            ),

            deliveryType: (int) config(
                'shipping.providers.pathao.delivery_type',
                48,
            ),

            itemType: (int) config(
                'shipping.providers.pathao.item_type',
                2,
            ),

            defaultItemWeightKg: (float) config(
                'shipping.providers.pathao.default_item_weight_kg',
                0.5,
            ),

            connectTimeoutSeconds: (int) config(
                'shipping.providers.pathao.connect_timeout_seconds',
                5,
            ),

            timeoutSeconds: (int) config(
                'shipping.providers.pathao.timeout_seconds',
                15,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function arrayString(
        array $values,
        string $key,
    ): ?string {
        $value =
            $values[$key] ??
            null;

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function arrayInt(
        array $values,
        string $key,
    ): ?int {
        $value =
            $values[$key] ??
            null;

        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value) &&
            ctype_digit(
                $value,
            )
        ) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function arrayFloat(
        array $values,
        string $key,
    ): ?float {
        $value =
            $values[$key] ??
            null;

        if (
            ! is_int($value) &&
            ! is_float($value) &&
            ! is_string($value)
        ) {
            return null;
        }

        if (
            ! is_numeric(
                $value,
            )
        ) {
            return null;
        }

        return (float) $value;
    }

    private function nullableConfigString(
        string $key,
    ): ?string {
        $value =
            config(
                $key,
            );

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }

    private function configString(
        string $key,
        string $default,
    ): string {
        return $this
            ->nullableConfigString(
                $key,
            ) ??
            $default;
    }
}
