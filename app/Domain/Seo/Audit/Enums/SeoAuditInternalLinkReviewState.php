<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditInternalLinkReviewState: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';
}
