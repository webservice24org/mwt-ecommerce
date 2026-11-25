<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantReturnFees: string
{
    case Free = 'free';
    case CustomerResponsibility = 'customer_responsibility';
    case ShippingFee = 'shipping_fee';

    public function schemaUrl(): string
    {
        return match ($this) {
            self::Free => 'https://schema.org/FreeReturn',

            self::CustomerResponsibility => 'https://schema.org/ReturnFeesCustomerResponsibility',

            self::ShippingFee => 'https://schema.org/ReturnShippingFees',
        };
    }
}
