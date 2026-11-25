<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Providers\OpenAiResponsesSeoProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiSeoProviderConfigurationSafetyTest extends TestCase
{
    public function test_provider_is_disabled_by_default_and_makes_no_http_request(): void
    {
        config([
            'ai-seo.enabled' => false,

            'ai-seo.openai.api_key' => 'test-key',
        ]);

        Http::fake();

        $this->expectException(
            AiSeoProviderException::class,
        );

        try {
            app(
                OpenAiResponsesSeoProvider::class,
            )->generate([
                'prompt_version' => 'ai-seo-metadata-v1',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }
}
