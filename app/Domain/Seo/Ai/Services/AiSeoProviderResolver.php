<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Contracts\AiSeoProvider;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Providers\OpenAiResponsesSeoProvider;

final readonly class AiSeoProviderResolver
{
    public function __construct(
        private OpenAiResponsesSeoProvider $openAi,
    ) {}

    public function resolve(
        ?string $driver = null,
    ): AiSeoProvider {
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
                'ai_seo_provider_unsupported',
                sprintf(
                    'Unsupported AI SEO provider: %s.',
                    $driver,
                ),
            ),
        };
    }
}
