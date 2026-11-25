<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantFeedExportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
