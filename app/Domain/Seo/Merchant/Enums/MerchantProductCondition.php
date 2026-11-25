<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantProductCondition: string
{
    case New = 'new';
    case Refurbished = 'refurbished';
    case Used = 'used';
}
