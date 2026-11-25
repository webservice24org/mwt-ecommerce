<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditSeverity: string
{
    case Critical = 'critical';
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
