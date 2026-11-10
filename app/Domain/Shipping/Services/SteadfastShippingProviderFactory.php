<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Providers\SteadfastShippingProvider;
use App\Models\ShippingProviderAccount;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

final readonly class SteadfastShippingProviderFactory
{
    public function fromAccount(
        ShippingProviderAccount $account,
    ): SteadfastShippingProvider {
        if (
            $account->provider_code !==
            ShippingProviderCode::Steadfast
        ) {
            throw new InvalidArgumentException(
                'The courier account is not a Steadfast account.',
            );
        }

        $credentials =
            $account->credentials ??
            [];

        $apiKey =
            $this->requiredCredential(
                $credentials,
                'api_key',
                'Steadfast API Key is not configured.',
            );

        $secretKey =
            $this->requiredCredential(
                $credentials,
                'secret_key',
                'Steadfast Secret Key is not configured.',
            );

        return new SteadfastShippingProvider(
            baseUrl: $this->configString(
                'shipping.providers.steadfast.base_url',
                'https://portal.packzy.com/api/v1',
            ),

            apiKey: $apiKey,

            secretKey: $secretKey,

            connectTimeoutSeconds: (int) config(
                'shipping.providers.steadfast.connect_timeout_seconds',
                5,
            ),

            timeoutSeconds: (int) config(
                'shipping.providers.steadfast.timeout_seconds',
                15,
            ),
        );
    }

    public function runtimeProvider(
    ): ?SteadfastShippingProvider {
        if (
            ! Schema::hasTable(
                'shipping_provider_accounts',
            )
        ) {
            return null;
        }

        $account =
            ShippingProviderAccount::query()
                ->where(
                    'provider_code',
                    ShippingProviderCode::Steadfast
                        ->value,
                )
                ->first();

        if (
            ! $account instanceof ShippingProviderAccount
        ) {
            return null;
        }

        /*
         * Only Connected + Active accounts
         * are registered at runtime.
         */
        if (
            ! $account->isOperational()
        ) {
            return null;
        }

        return $this->fromAccount(
            $account,
        );
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function requiredCredential(
        array $credentials,
        string $key,
        string $message,
    ): string {
        $value =
            $credentials[
                $key
            ] ?? null;

        if (
            ! is_string(
                $value,
            )
        ) {
            throw new RuntimeException(
                $message,
            );
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
        ) {
            throw new RuntimeException(
                $message,
            );
        }

        return $value;
    }

    private function configString(
        string $key,
        string $default,
    ): string {
        $value =
            config(
                $key,
            );

        if (
            ! is_string(
                $value,
            )
        ) {
            return $default;
        }

        $value =
            trim(
                $value,
            );

        return
            $value === ''
            ? $default
            : $value;
    }
}
