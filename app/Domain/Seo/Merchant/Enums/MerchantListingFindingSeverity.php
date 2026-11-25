<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantListingFindingSeverity: string
{
    case Blocker = 'blocker';
    case Warning = 'warning';
    case Info = 'info';
}
