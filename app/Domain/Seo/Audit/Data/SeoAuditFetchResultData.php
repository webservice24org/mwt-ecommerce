<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Data;

final readonly class SeoAuditFetchResultData
{
    public function __construct(
        public int $status,
        public ?string $contentType,
        public ?string $redirectLocation,
        public ?string $xRobotsTag,
        public ?string $body,
        public int $bodyBytes,
    ) {}

    public function isRedirect(): bool
    {
        return $this->status >= 300
            && $this->status <= 399;
    }

    public function isHtml(): bool
    {
        if ($this->contentType === null) {
            return false;
        }

        return str_contains(
            strtolower($this->contentType),
            'text/html',
        );
    }
}
