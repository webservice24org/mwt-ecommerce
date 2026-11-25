<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Data;

final readonly class AiSeoProviderResultData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public array $payload,
        public array $rawResponse,
    ) {}
}
