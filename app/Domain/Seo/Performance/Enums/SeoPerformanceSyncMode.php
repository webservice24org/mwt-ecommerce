<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceSyncMode: string
{
    case Manual = 'manual';
    case Incremental = 'incremental';
    case Backfill = 'backfill';
}
