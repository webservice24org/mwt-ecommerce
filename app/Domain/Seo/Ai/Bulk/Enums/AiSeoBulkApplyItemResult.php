<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkApplyItemResult: string
{
    case Applied = 'applied';
    case AlreadyApplied = 'already_applied';
    case Conflict = 'conflict';
    case Stale = 'stale';
    case NotApproved = 'not_approved';
    case Failed = 'failed';
}
