<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantReturnPolicyCategory: string
{
    case Finite = 'finite';
    case NotPermitted = 'not_permitted';
    case Unlimited = 'unlimited';

    public function schemaUrl(): string
    {
        return match ($this) {
            self::Finite => 'https://schema.org/MerchantReturnFiniteReturnWindow',

            self::NotPermitted => 'https://schema.org/MerchantReturnNotPermitted',

            self::Unlimited => 'https://schema.org/MerchantReturnUnlimitedWindow',
        };
    }
}
