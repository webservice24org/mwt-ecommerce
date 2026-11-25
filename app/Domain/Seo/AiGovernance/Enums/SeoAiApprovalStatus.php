<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Enums;

enum SeoAiApprovalStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
