<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SeoPerformanceProvider: string
{
    case SearchConsole = 'search_console';
    case GoogleAnalytics = 'google_analytics';
}
