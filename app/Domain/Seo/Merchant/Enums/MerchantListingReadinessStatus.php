<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantListingReadinessStatus: string
{
    case Ready = 'ready';
    case NeedsWork = 'needs_work';
    case Blocked = 'blocked';
}
