<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Contracts;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Data\SeoPerformanceVerificationData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Models\SeoPerformanceConnection;

interface SeoPerformanceProviderAdapter
{
    public function provider(): SeoPerformanceProvider;

    /** @return list<SeoPerformanceCapability> */
    public function capabilities(): array;

    public function verify(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceVerificationData;

    public function fetchPage(
        SeoPerformanceConnection $connection,
        SeoPerformanceFetchRequestData $request,
    ): SeoPerformanceFetchPageData;
}
