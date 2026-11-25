<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditRunStatus: string
{
    case Requested = 'requested';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
