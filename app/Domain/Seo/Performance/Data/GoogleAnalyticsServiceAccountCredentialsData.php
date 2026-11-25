<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class GoogleAnalyticsServiceAccountCredentialsData
{
    public function __construct(
        public string $clientEmail,
        public string $privateKey,
        public ?string $privateKeyId,
    ) {}
}
