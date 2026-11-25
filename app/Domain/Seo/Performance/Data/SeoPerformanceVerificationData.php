<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;

final readonly class SeoPerformanceVerificationData
{
    /**
     * @param  list<SeoPerformanceCapability>  $capabilities
     */
    public function __construct(
        public bool $verified,
        public array $capabilities,
        public ?string $errorCode = null,
        public ?string $message = null,
    ) {}
}
