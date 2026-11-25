<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;

final class SeoPerformanceCapabilityRegistry
{
    /** @return list<SeoPerformanceCapability> */
    public function forProvider(
        SeoPerformanceProvider $provider,
    ): array {
        return match ($provider) {
            SeoPerformanceProvider::SearchConsole => [
                SeoPerformanceCapability::SearchQueries,
                SeoPerformanceCapability::SearchLandingPages,
            ],
            SeoPerformanceProvider::GoogleAnalytics => [
                SeoPerformanceCapability::AnalyticsLandingPages,
            ],
        };
    }
}
