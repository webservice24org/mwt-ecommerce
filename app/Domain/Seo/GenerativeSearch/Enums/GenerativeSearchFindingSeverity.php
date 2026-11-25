<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Enums;

enum GenerativeSearchFindingSeverity: string
{
    case Blocker = 'blocker';
    case Warning = 'warning';
    case Info = 'info';
}
