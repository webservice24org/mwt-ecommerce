<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditHtmlImageObservationData
{
    public function __construct(
        public string $reference,
        public bool $altAttributePresent,
        public ?string $altText,
        public int $occurrences = 1,
    ) {}
}
