<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use App\Models\ShippingProviderAccount;

final readonly class ShippingProviderAccountResolver
{
    public function connected(
        ShippingProviderCode $providerCode,
    ): ?ShippingProviderAccount {
        if (
            ! $providerCode
                ->requiresAccount()
        ) {
            return null;
        }

        return ShippingProviderAccount::query()
            ->where(
                'provider_code',
                $providerCode->value,
            )
            ->where(
                'is_active',
                true,
            )
            ->where(
                'connection_status',
                ShippingProviderConnectionStatus::Connected
                    ->value,
            )
            ->first();
    }

    public function default(
    ): ?ShippingProviderAccount {
        return ShippingProviderAccount::query()
            ->where(
                'is_default',
                true,
            )
            ->where(
                'is_active',
                true,
            )
            ->where(
                'connection_status',
                ShippingProviderConnectionStatus::Connected
                    ->value,
            )
            ->first();
    }
}
