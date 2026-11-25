<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkJobStatus: string
{
    case Planned = 'planned';
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
