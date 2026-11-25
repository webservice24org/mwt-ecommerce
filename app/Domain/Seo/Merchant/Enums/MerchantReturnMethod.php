<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantReturnMethod: string
{
    case ByMail = 'by_mail';
    case InStore = 'in_store';
    case AtKiosk = 'at_kiosk';

    public function schemaUrl(): string
    {
        return match ($this) {
            self::ByMail => 'https://schema.org/ReturnByMail',

            self::InStore => 'https://schema.org/ReturnInStore',

            self::AtKiosk => 'https://schema.org/ReturnAtKiosk',
        };
    }
}
