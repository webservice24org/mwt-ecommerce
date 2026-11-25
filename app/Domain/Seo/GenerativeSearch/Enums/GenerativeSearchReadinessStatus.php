<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Enums;

enum GenerativeSearchReadinessStatus: string
{
    case Ready = 'ready';
    case NeedsWork = 'needs_work';
    case Blocked = 'blocked';
}
