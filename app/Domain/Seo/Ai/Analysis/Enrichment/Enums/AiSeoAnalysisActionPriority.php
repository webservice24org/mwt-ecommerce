<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Enums;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisSeverity;

enum AiSeoAnalysisActionPriority: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public static function fromSeverity(
        AiSeoAnalysisSeverity $severity,
    ): self {
        return match ($severity) {
            AiSeoAnalysisSeverity::Critical => self::High,

            AiSeoAnalysisSeverity::Warning => self::Medium,

            AiSeoAnalysisSeverity::Info => self::Low,
        };
    }
}
