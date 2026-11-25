<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkReviewItemResult: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case AlreadyDecided = 'already_decided';
    case BlockedStale = 'blocked_stale';
    case Failed = 'failed';
}
