<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditHtmlLinkObservationData
{
    public function __construct(
        public string $reference,
        public ?string $anchorText,
        public bool $nofollow,
    ) {}
}
