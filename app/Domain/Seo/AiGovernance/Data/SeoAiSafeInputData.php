<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Data;

final readonly class SeoAiSafeInputData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $fieldNames
     */
    public function __construct(
        public array $payload,
        public string $snapshotHash,
        public array $fieldNames,
    ) {}
}
