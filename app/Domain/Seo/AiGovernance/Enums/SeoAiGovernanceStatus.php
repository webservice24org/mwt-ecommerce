<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Enums;

enum SeoAiGovernanceStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
