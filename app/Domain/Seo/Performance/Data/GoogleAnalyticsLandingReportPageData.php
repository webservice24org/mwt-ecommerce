<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class GoogleAnalyticsLandingReportPageData
{
    /**
     * @param list<array{
     *     date: string,
     *     landing_page: string,
     *     sessions: float,
     *     engaged_sessions: float,
     *     active_users: float,
     *     new_users: float,
     *     key_events: float,
     *     total_revenue: float
     * }> $rows
     */
    public function __construct(
        public array $rows,
        public int $rowCount,
    ) {}
}
