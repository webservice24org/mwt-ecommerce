<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditCrawlState: string
{
    case Discovered = 'discovered';
    case Failed = 'failed';
    case Fetched = 'fetched';

    public function rank(): int
    {
        return match ($this) {
            self::Discovered => 0,
            self::Failed => 1,
            self::Fetched => 2,
        };
    }
}
