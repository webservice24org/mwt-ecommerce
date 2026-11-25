<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Contracts\AiSeoProvider;
use App\Domain\Seo\Ai\Data\AiSeoPromptContextData;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;
use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Services\SeoAiLegacyRequestGovernance;
use App\Domain\Seo\AiGovernance\Support\SeoAiProviderUsageExtractor;
use App\Models\AiSeoRequest;
use App\Models\SeoAiGovernanceRun;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class AiSeoSuggestionGenerationService
{
    public function __construct(
        private AiSeoProviderResolver $providers,
        private AiSeoSuggestionValidator $validator,
        private AiSeoRequestLifecycleManager $lifecycle,
        private SeoAiLegacyRequestGovernance $governance,
        private SeoAiProviderUsageExtractor $usageExtractor,
    ) {}

    public function execute(
        AiSeoRequest $request,
        ?AiSeoProvider $provider = null,
    ): AiSeoRequest {
        $provider ??= $this->providers->resolve();

        $processing = $this->lifecycle->start(
            request: $request,
            provider: $provider->name(),
            modelName: $provider->model(),
            promptVersion: $this->promptVersion($request),
        );

        $governanceRun = null;
        $usage = null;

        try {
            $governanceRun = $this->governance->begin(
                request: $processing,
                provider: $provider->name(),
                model: $provider->model(),
                promptVersion: $this->promptVersion($processing),
            );

            $result = $provider->generate($processing->input_snapshot);

            $usage = $this->usageExtractor->extract($result->rawResponse);

            $suggestions = $this->validator->validate(
                payload: $result->payload,
                inputSnapshot: $processing->input_snapshot,
            );

            return DB::transaction(
                function () use (
                    $governanceRun,
                    $result,
                    $processing,
                    $suggestions,
                    $usage,
                ): AiSeoRequest {
                    $this->governance->succeed(
                        run: $governanceRun,
                        validatedProviderPayload: $result->payload,
                        usage: $usage,
                    );

                    return $this->lifecycle->complete(
                        request: $processing,
                        suggestions: $suggestions,
                        rawResponse: $result->rawResponse,
                    );
                },
            );
        } catch (Throwable $exception) {
            $this->markFailed($processing, $exception);

            if ($governanceRun instanceof SeoAiGovernanceRun) {
                try {
                    $this->governance->fail(
                        run: $governanceRun,
                        failureCode: $this->failureCode($exception),
                        usage: $usage instanceof SeoAiUsageData ? $usage : null,
                    );
                } catch (Throwable) {
                    /*
                     * Preserve the original provider/validation/lifecycle
                     * exception. Governance failure recording must not hide
                     * the root cause from the caller.
                     */
                }
            }

            throw $exception;
        }
    }

    private function promptVersion(AiSeoRequest $request): string
    {
        $value = $request->input_snapshot['prompt_version'] ?? null;

        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : AiSeoPromptContextData::PROMPT_VERSION;
    }

    private function markFailed(
        AiSeoRequest $request,
        Throwable $exception,
    ): void {
        $errorCode = $this->failureCode($exception);
        $message = trim($exception->getMessage());

        if ($message === '') {
            $message = 'AI SEO generation failed.';
        }

        $message = mb_substr($message, 0, 1000);

        try {
            $this->lifecycle->fail(
                request: $request,
                errorCode: $errorCode,
                errorMessage: $message,
            );
        } catch (Throwable) {
            /*
             * Preserve the original provider/validation exception. A lifecycle
             * failure must not hide the root cause from the caller.
             */
        }
    }

    private function failureCode(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AiSeoProviderException => $exception->errorCode,
            $exception instanceof AiSeoSuggestionValidationException => 'invalid_provider_output',
            default => 'ai_seo_generation_failed',
        };
    }
}
