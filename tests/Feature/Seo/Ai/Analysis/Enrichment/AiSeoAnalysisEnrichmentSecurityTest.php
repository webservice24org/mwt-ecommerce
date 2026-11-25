<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis\Enrichment;

use App\Domain\Seo\Ai\Analysis\Enrichment\Providers\OpenAiResponsesAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Models\AiSeoAnalysisEnrichment;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiSeoAnalysisEnrichmentSecurityTest extends TestCase
{
    public function test_real_provider_path_requires_both_master_and_enrichment_switches_and_sends_nothing_when_disabled(): void
    {
        config([
            'ai-seo.enabled' => true,

            'ai-seo-analysis.enrichment_enabled' => false,

            'ai-seo.openai.api_key' => 'test-key',
        ]);

        Http::fake();

        $this->expectException(
            AiSeoProviderException::class,
        );

        try {
            app(
                OpenAiResponsesAnalysisEnrichmentProvider::class,
            )->generate([
                'deterministic_analysis' => [
                    'findings' => [
                        [
                            'id' => 'social_image_missing',
                        ],
                    ],
                ],
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_api_key_and_raw_provider_response_body_are_not_enrichment_model_fields(): void
    {
        $model =
            new AiSeoAnalysisEnrichment;

        self::assertNotContains(
            'api_key',
            $model->getFillable(),
        );

        self::assertNotContains(
            'raw_response',
            $model->getFillable(),
        );
    }
}
