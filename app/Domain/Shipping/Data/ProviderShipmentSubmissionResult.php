<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Data;

final readonly class ProviderShipmentSubmissionResult
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ?string $externalReference,
        public ?string $carrier = null,
        public ?string $service = null,
        public ?string $trackingNumber = null,
        public ?string $trackingUrl = null,
        public ?array $metadata = null,
    ) {}
}
