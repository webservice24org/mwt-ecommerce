<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class SeoPerformanceFetchPageData
{
    /**
     * @param  list<array<string, bool|float|int|string|null>>  $rows
     */
    public function __construct(
        public array $rows,
        public ?string $nextPageToken,
        public bool $complete,
        public bool $truncated = false,
    ) {}
}
