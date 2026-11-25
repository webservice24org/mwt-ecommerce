<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;

final readonly class SeoAuditImageHealthResultData
{
    public function __construct(
        public SeoAuditImageHealthState $state,
        public ?int $httpStatus = null,
        public ?string $contentType = null,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
    ) {}
}
