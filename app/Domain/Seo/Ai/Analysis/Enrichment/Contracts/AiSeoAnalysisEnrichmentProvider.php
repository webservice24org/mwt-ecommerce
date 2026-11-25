<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Contracts;

use App\Domain\Seo\Ai\Data\AiSeoProviderResultData;

interface AiSeoAnalysisEnrichmentProvider
{
    public function name(): string;

    public function model(): string;

    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(
        array $context,
    ): AiSeoProviderResultData;
}
