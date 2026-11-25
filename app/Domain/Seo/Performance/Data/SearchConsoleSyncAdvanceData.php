<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class SearchConsoleSyncAdvanceData
{
    public function __construct(
        public bool $completed,
        public int $queuePass,
        public int $rowsReceived,
        public int $rowsPersisted,
        public bool $truncated,
    ) {}
}
