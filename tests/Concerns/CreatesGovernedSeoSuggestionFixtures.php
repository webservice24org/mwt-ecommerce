<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Domain\Seo\AiGovernance\Services\SeoAiLegacyRequestGovernance;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;

trait CreatesGovernedSeoSuggestionFixtures
{
    private function governSeoFixture(AiSeoRequest $request): void
    {
        $governance = app(SeoAiLegacyRequestGovernance::class);
        $run = $governance->begin(
            request: $request,
            provider: 'test-provider',
            model: 'test-model',
            promptVersion: 'ai-seo-metadata-v1',
        );

        $suggestions = AiSeoSuggestion::query()
            ->where('ai_seo_request_id', $request->id)
            ->orderBy('position')
            ->get()
            ->map(static fn (AiSeoSuggestion $suggestion): array => [
                'field' => $suggestion->field->value,
                'value' => $suggestion->value,
                'rationale' => $suggestion->rationale,
            ])
            ->all();

        $governance->succeed($run, ['suggestions' => $suggestions]);
    }
}
