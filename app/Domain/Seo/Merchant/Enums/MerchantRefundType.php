<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantRefundType: string
{
    case Full = 'full';
    case Exchange = 'exchange';
    case StoreCredit = 'store_credit';

    public function schemaUrl(): string
    {
        return match ($this) {
            self::Full => 'https://schema.org/FullRefund',

            self::Exchange => 'https://schema.org/ExchangeRefund',

            self::StoreCredit => 'https://schema.org/StoreCreditRefund',
        };
    }
}
