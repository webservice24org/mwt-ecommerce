<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceSyncScope: string
{
    case SearchPerformance = 'search_performance';
    case LandingAnalytics = 'landing_analytics';
    case StructuredDataIssues = 'structured_data_issues';
    case GenerativeAiVisibility = 'generative_ai_visibility';

    public function requiredCapability(): SeoPerformanceCapability
    {
        return match ($this) {
            self::SearchPerformance => SeoPerformanceCapability::SearchQueries,
            self::LandingAnalytics => SeoPerformanceCapability::AnalyticsLandingPages,
            self::StructuredDataIssues => SeoPerformanceCapability::StructuredDataIssues,
            self::GenerativeAiVisibility => SeoPerformanceCapability::GenerativeAiVisibility,
        };
    }
}
