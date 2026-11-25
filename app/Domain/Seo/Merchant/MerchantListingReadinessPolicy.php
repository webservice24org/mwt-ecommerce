<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant;

final class MerchantListingReadinessPolicy
{
    public const VERSION =
        'merchant-listing-readiness-v1';

    /**
     * @return list<string>
     */
    public static function supportedProductTypes(): array
    {
        return [
            'simple',
            'variable',
        ];
    }

    /**
     * @return list<string>
     */
    public static function merchantProductStructuredDataTypes(): array
    {
        return [
            'Product',
            'ProductGroup',
        ];
    }
}
