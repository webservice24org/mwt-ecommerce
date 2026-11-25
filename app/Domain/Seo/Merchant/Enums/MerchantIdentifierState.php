<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantIdentifierState: string
{
    case Available = 'available';
    case NotAvailableDeclared = 'not_available_declared';
    case Unknown = 'unknown';
}
