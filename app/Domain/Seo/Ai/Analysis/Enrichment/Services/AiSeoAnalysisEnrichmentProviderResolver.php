<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Services;

use App\Domain\Seo\Ai\Analysis\Enrichment\Contracts\AiSeoAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Analysis\Enrichment\Providers\OpenAiResponsesAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;

final readonly class AiSeoAnalysisEnrichmentProviderResolver
{
    public function __construct(
        private OpenAiResponsesAnalysisEnrichmentProvider $openAi,
    ) {}

    public function resolve(
        ?string $driver = null,
    ): AiSeoAnalysisEnrichmentProvider {
        $driver =
            trim(
                $driver
                ?? (string) config(
                    'ai-seo.default',
                    'openai',
                ),
            );

        return match ($driver) {
            'openai' => $this->openAi,

            default => throw new AiSeoProviderException(
                'ai_seo_analysis_provider_unsupported',
                sprintf(
                    'Unsupported AI SEO analysis enrichment provider: %s.',
                    $driver,
                ),
            ),
        };
    }
}
