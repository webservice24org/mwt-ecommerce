<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceCapability: string
{
    case SearchQueries = 'search_queries';
    case SearchLandingPages = 'search_landing_pages';
    case AnalyticsLandingPages = 'analytics_landing_pages';
    case StructuredDataIssues = 'structured_data_issues';
    case GenerativeAiVisibility = 'generative_ai_visibility';
}
