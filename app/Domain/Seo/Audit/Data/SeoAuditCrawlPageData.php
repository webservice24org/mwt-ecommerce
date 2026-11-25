<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;

final readonly class SeoAuditCrawlPageData
{
    public function __construct(
        public string $url,
        public SeoAuditCrawlState $state = SeoAuditCrawlState::Discovered,
        public int $depth = 0,
        public SeoAuditSubjectType $subjectType = SeoAuditSubjectType::Url,
        public ?string $subjectKey = null,
        public ?int $httpStatus = null,
        public ?string $contentType = null,
        public ?string $redirectUrl = null,
        public ?string $canonicalUrl = null,
        public ?bool $isIndexable = null,
        public ?string $title = null,
        public ?string $metaDescription = null,
        public ?int $h1Count = null,
        public ?int $wordCount = null,
        public ?string $fetchErrorCode = null,
        public ?string $fetchErrorMessage = null,
    ) {}
}
