<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Enums;

enum MerchantFeedExportEventType: string
{
    case Requested = 'requested';
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retried = 'retried';
    case DownloadRequested = 'download_requested';
}
