<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkPlannedAction: string
{
    case Generate = 'generate';
    case Skip = 'skip';
}
