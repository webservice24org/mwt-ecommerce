<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditImageHealthSummaryData
{
    public function __construct(
        public int $observations,
        public int $resources,
        public int $healthy,
        public int $broken,
        public int $redirected,
        public int $skippedExternal,
    ) {}
}
