<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Enums;

enum AiSeoSuggestionReviewDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
