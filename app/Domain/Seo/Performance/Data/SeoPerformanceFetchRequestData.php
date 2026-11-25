<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use DateTimeImmutable;

final readonly class SeoPerformanceFetchRequestData
{
    public function __construct(
        public SeoPerformanceSyncScope $scope,
        public DateTimeImmutable $dateFrom,
        public DateTimeImmutable $dateTo,
        public int $rowLimit,
        public ?string $pageToken = null,
    ) {}
}
