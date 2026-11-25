<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditHtmlObservationData
{
    /**
     * @param  list<SeoAuditHtmlLinkObservationData>  $links
     * @param  list<SeoAuditHtmlImageObservationData>  $images
     */
    public function __construct(
        public ?string $title,
        public ?string $metaDescription,
        public ?string $canonicalReference,
        public bool $metaNoindex,
        public int $h1Count,
        public int $wordCount,
        public array $links,
        public array $images,
    ) {}
}
