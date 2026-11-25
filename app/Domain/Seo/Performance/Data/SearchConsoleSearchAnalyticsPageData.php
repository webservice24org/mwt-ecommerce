<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class SearchConsoleSearchAnalyticsPageData
{
    /**
     * @param  list<array{keys: list<string>, clicks: float, impressions: float, ctr: float, position: float}>  $rows
     */
    public function __construct(
        public array $rows,
    ) {}
}
