<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditImageHealthState: string
{
    case Healthy = 'healthy';
    case Broken = 'broken';
    case Redirected = 'redirected';
    case SkippedExternal = 'skipped_external';
}
