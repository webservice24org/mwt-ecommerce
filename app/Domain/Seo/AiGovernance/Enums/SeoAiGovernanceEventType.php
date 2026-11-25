<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Enums;

enum SeoAiGovernanceEventType: string
{
    case Requested = 'requested';
    case Started = 'started';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
}
