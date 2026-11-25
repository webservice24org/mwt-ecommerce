<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\Seo\Ai\Providers\OpenAiResponsesSeoProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OpenAiResponsesSeoProviderTest extends TestCase
{
    public function test_it_uses_responses_api_structured_output_and_disables_provider_storage(): void
    {
        config([
            'ai-seo.enabled' => true,

            'ai-seo.openai.api_key' => 'test-key',

            'ai-seo.openai.base_url' => 'https://api.openai.test/v1',

            'ai-seo.openai.model' => 'gpt-6-luna',

            'ai-seo.openai.timeout_seconds' => 20,

            'ai-seo.openai.max_output_tokens' => 900,
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response(
                [
                    'id' => 'resp_test_123',

                    'output' => [
                        [
                            'type' => 'message',

                            'content' => [
                                [
                                    'type' => 'output_text',

                                    'text' => json_encode(
                                        [
                                            'suggestions' => [
                                                [
                                                    'field' => 'meta_title',

                                                    'value' => 'USB Cable | MWT Store',

                                                    'rationale' => 'Clear product title.',
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
                OpenAiResponsesSeoProvider::class,
            );

        $result =
            $provider
                ->generate(
                    $this->context(),
                );

        self::assertSame(
            'USB Cable | MWT Store',
            $result
                ->payload[
                    'suggestions'
                ][0]['value'],
        );

        self::assertSame(
            'resp_test_123',
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

                return $request->url()
                    === 'https://api.openai.test/v1/responses'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer test-key',
                    )
                    && ($body['model'] ?? null)
                        === 'gpt-6-luna'
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
                    && ($body[
                        'text'
                    ]['format']['schema']['additionalProperties']
                        ?? null)
                        === false;
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function context(): array
    {
        return [
            'prompt_version' => 'ai-seo-metadata-v1',

            'task' => 'generate_metadata_suggestions',

            'site' => [
                'name' => 'MWT Store',
            ],

            'rules' => [
                'Use only supplied facts.',
            ],

            'target_fields' => [
                'meta_title' => [
                    'max_length' => 180,

                    'purpose' => 'Search result title.',
                ],
            ],

            'entity_snapshot' => [
                'entity' => [
                    'type' => 'product',

                    'id' => 1,

                    'name' => 'USB Cable',

                    'slug' => 'usb-cable',
                ],
            ],
        ];
    }
}
