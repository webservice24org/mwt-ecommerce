<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditInternalLinkData
{
    public function __construct(
        public string $sourceUrl,
        public string $targetReference,
        public int $sourceDepth = 0,
        public ?string $anchorText = null,
        public bool $nofollow = false,
        public int $occurrences = 1,
    ) {}
}
