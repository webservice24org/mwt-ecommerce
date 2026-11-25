<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Enums;

enum SeoAiGovernancePurpose: string
{
    case SeoSuggestion = 'seo_suggestion';
    case SeoAnalysis = 'seo_analysis';
    case BulkOptimization = 'bulk_optimization';
    case ImageAltSuggestion = 'image_alt_suggestion';
    case PerformanceRecommendation = 'performance_recommendation';
}
