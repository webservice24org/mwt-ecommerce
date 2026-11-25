<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enums;

enum AiSeoAnalysisSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
}
