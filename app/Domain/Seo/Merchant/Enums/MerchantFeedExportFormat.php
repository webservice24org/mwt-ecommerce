<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantFeedExportFormat: string
{
    case Tsv = 'tsv';
}
