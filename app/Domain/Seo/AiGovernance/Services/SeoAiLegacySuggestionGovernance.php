<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Services;

use App\Models\Admin;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\SeoAiGovernanceRun;
use Illuminate\Validation\ValidationException;

final readonly class SeoAiLegacySuggestionGovernance
{
    public function __construct(
        private SeoAiLegacyRequestGovernance $requests,
    ) {}

    public function approve(
        AiSeoSuggestion $suggestion,
        Admin $reviewer,
    ): SeoAiGovernanceRun {
        return $this->requests->approve(
            $this->requestFor($suggestion),
            $reviewer,
        );
    }

    public function reject(
        AiSeoSuggestion $suggestion,
        Admin $reviewer,
    ): SeoAiGovernanceRun {
        return $this->requests->reject(
            $this->requestFor($suggestion),
            $reviewer,
        );
    }

    public function markPublished(
        AiSeoSuggestion $suggestion,
        Admin $actor,
    ): SeoAiGovernanceRun {
        return $this->requests->markPublished(
            $this->requestFor($suggestion),
            $actor,
        );
    }

    private function requestFor(
        AiSeoSuggestion $suggestion,
    ): AiSeoRequest {
        $value = $suggestion->getAttribute('ai_seo_request_id');

        if (
            (! is_int($value) && ! is_string($value))
            || ! ctype_digit((string) $value)
            || (int) $value < 1
        ) {
            throw ValidationException::withMessages([
                'suggestion' => 'AI SEO suggestion is not linked to a valid request.',
            ]);
        }

        return AiSeoRequest::query()->findOrFail((int) $value);
    }
}
