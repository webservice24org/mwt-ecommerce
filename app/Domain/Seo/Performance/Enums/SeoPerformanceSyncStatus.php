<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceSyncStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
