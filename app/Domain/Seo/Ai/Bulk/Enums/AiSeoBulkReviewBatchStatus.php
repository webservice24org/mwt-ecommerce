<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Enums;

enum AiSeoBulkReviewBatchStatus: string
{
    case Completed = 'completed';
    case CompletedWithIssues = 'completed_with_issues';
}
