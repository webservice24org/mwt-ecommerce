<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enums;

enum AiSeoAnalysisGrade: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case NeedsWork = 'needs_work';
    case Poor = 'poor';
}
