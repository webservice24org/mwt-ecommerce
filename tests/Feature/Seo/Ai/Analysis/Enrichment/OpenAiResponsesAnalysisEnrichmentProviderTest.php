<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis\Enrichment;

use App\Domain\Seo\Ai\Analysis\Enrichment\Providers\OpenAiResponsesAnalysisEnrichmentProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OpenAiResponsesAnalysisEnrichmentProviderTest extends TestCase
{
    public function test_it_uses_dynamic_finding_allowlist_structured_output_and_disables_storage(): void
    {
        config([
            'ai-seo.enabled' => true,

            'ai-seo-analysis.enrichment_enabled' => true,

            'ai-seo-analysis.max_actions' => 5,

            'ai-seo-analysis.max_output_tokens' => 1400,

            'ai-seo.openai.api_key' => 'test-key',

            'ai-seo.openai.base_url' => 'https://api.openai.test/v1',

            'ai-seo.openai.model' => 'gpt-6-luna',

            'ai-seo.openai.timeout_seconds' => 20,
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response(
                [
                    'id' => 'resp_analysis_123',

                    'output' => [
                        [
                            'type' => 'message',

                            'content' => [
                                [
                                    'type' => 'output_text',

                                    'text' => json_encode(
                                        [
                                            'summary' => 'Two deterministic findings should be reviewed.',

                                            'actions' => [
                                                [
                                                    'finding_id' => 'missing_resolved_description',

                                                    'explanation' => 'The resolved search description is absent.',

                                                    'action' => 'Add a factual description from the existing entity content.',
                                                ],
                                            ],
                                        ],
                                        JSON_THROW_ON_ERROR,
                                    ),
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $provider =
            app(
                OpenAiResponsesAnalysisEnrichmentProvider::class,
            );

        $result =
            $provider
                ->generate(
                    $this->context(),
                );

        self::assertSame(
            'Two deterministic findings should be reviewed.',
            $result
                ->payload[
                    'summary'
                ],
        );

        self::assertSame(
            'resp_analysis_123',
            $result
                ->rawResponse[
                    'id'
                ],
        );

        Http::assertSent(
            function (
                Request $request,
            ): bool {
                $body =
                    $request->data();

                $findingEnum =
                    $body[
                        'text'
                    ]['format']['schema']['properties']['actions']['items']['properties']['finding_id']['enum']
                    ?? null;

                return $request->url()
                    === 'https://api.openai.test/v1/responses'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer test-key',
                    )
                    && ($body['store'] ?? null)
                        === false
                    && ($body[
                        'text'
                    ]['format']['type'] ?? null)
                        === 'json_schema'
                    && ($body[
                        'text'
                    ]['format']['strict'] ?? null)
                        === true
                    && $findingEnum
                        === [
                            'missing_resolved_description',
                            'social_image_missing',
                        ];
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function context(): array
    {
        return [
            'prompt_version' => 'ai-seo-analysis-enrichment-v1',

            'task' => 'enrich_existing_seo_analysis_findings',

            'analysis_identity' => [
                'analysis_id' => 10,
            ],

            'deterministic_analysis' => [
                'findings' => [
                    [
                        'id' => 'missing_resolved_description',
                    ],

                    [
                        'id' => 'social_image_missing',
                    ],
                ],
            ],
        ];
    }
}
