<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Services;

use App\Domain\Seo\Ai\Analysis\Enrichment\Contracts\AiSeoAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisEnrichmentStatus;
use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use DomainException;
use JsonException;
use Throwable;

final readonly class AiSeoAnalysisEnrichmentService
{
    public function __construct(
        private AiSeoAnalysisEnrichmentContextFactory $contextFactory,
        private AiSeoAnalysisEnrichmentProviderResolver $providers,
        private AiSeoAnalysisEnrichmentValidator $validator,
        private AiSeoSnapshotHasher $hasher,
    ) {}

    /**
     * @throws JsonException
     */
    public function enrich(
        int $analysisId,
        ?int $enrichedByAdminId = null,
        ?AiSeoAnalysisEnrichmentProvider $provider = null,
    ): AiSeoAnalysisEnrichment {
        $analysis =
            AiSeoAnalysis::query()
                ->findOrFail(
                    $analysisId,
                );

        if (
            $analysis->findings === []
        ) {
            throw new DomainException(
                'A deterministic SEO analysis with no findings does not require AI enrichment.',
            );
        }

        $context =
            $this
                ->contextFactory
                ->make(
                    $analysis,
                );

        $provider ??=
            $this
                ->providers
                ->resolve();

        $now =
            now();

        $enrichment =
            AiSeoAnalysisEnrichment::query()
                ->create([
                    'ai_seo_analysis_id' => $analysis
                        ->id,

                    'analysis_input_hash' => $analysis
                        ->analysis_input_hash,

                    'prompt_version' => AiSeoAnalysisEnrichmentContextFactory::PROMPT_VERSION,

                    'status' => AiSeoAnalysisEnrichmentStatus::Pending,

                    'provider' => $provider
                        ->name(),

                    'model_name' => $provider
                        ->model(),

                    'input_hash' => $this
                        ->hasher
                        ->hash(
                            $context,
                        ),

                    'input_context' => $context,

                    'summary' => null,

                    'actions' => null,

                    'provider_response_id' => null,

                    'error_code' => null,

                    'error_message' => null,

                    'enriched_by_admin_id' => $enrichedByAdminId,

                    'started_at' => $now,

                    'completed_at' => null,

                    'created_at' => $now,
                ]);

        try {
            $result =
                $provider
                    ->generate(
                        $context,
                    );

            $validated =
                $this
                    ->validator
                    ->validate(
                        payload: $result
                            ->payload,

                        analysis: $analysis,
                    );

            $payload =
                $validated
                    ->toArray();

            $enrichment->update([
                'status' => AiSeoAnalysisEnrichmentStatus::Completed,

                'summary' => $payload[
                        'summary'
                    ],

                'actions' => $payload[
                        'actions'
                    ],

                'provider_response_id' => $this->providerResponseId(
                    $result
                        ->rawResponse,
                ),

                'completed_at' => now(),
            ]);

            return $enrichment
                ->refresh();
        } catch (Throwable $exception) {
            $this->markFailed(
                $enrichment,
                $exception,
            );

            throw $exception;
        }
    }

    private function markFailed(
        AiSeoAnalysisEnrichment $enrichment,
        Throwable $exception,
    ): void {
        $errorCode =
            match (true) {
                $exception instanceof AiSeoProviderException => $exception
                    ->errorCode,

                $exception instanceof AiSeoAnalysisEnrichmentValidationException => 'invalid_analysis_enrichment_output',

                default => 'analysis_enrichment_failed',
            };

        $message =
            trim(
                $exception
                    ->getMessage(),
            );

        if ($message === '') {
            $message =
                'AI SEO analysis enrichment failed.';
        }

        $message =
            mb_substr(
                $message,
                0,
                1000,
            );

        try {
            $enrichment->update([
                'status' => AiSeoAnalysisEnrichmentStatus::Failed,

                'error_code' => $errorCode,

                'error_message' => $message,

                'completed_at' => now(),
            ]);
        } catch (Throwable) {
            /*
             * Preserve the original provider/validation exception.
             */
        }
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    private function providerResponseId(
        array $rawResponse,
    ): ?string {
        $value =
            $rawResponse[
                'id'
            ]
            ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        return mb_substr(
            $value,
            0,
            255,
        );
    }
}
