<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentService;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;
use App\Domain\Seo\Ai\Services\AiSeoDraftRequestService;
use App\Domain\Seo\Ai\Services\AiSeoSuggestionGenerationService;
use App\Models\AiSeoAnalysis;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AiSeoEntityWorkspaceActionController
{
    /**
     * @throws JsonException
     */
    public function analyze(
        Request $request,
        string $entityType,
        string $entityId,
        AiSeoEntityAnalysisService $analyses,
    ): RedirectResponse {
        [
            $type,
            $id,
        ] =
            $this->entityIdentity(
                $entityType,
                $entityId,
            );

        $analyses->analyze(
            entityType: $type,

            entityId: $id,

            analyzedByAdminId: $this->actorId(
                $request,
            ),
        );

        return back()->with(
            'success',
            'SEO analysis completed and appended to history.',
        );
    }

    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function generateSuggestions(
        Request $request,
        string $entityType,
        string $entityId,
        AiSeoDraftRequestService $drafts,
        AiSeoSuggestionGenerationService $generation,
    ): RedirectResponse {
        [
            $type,
            $id,
        ] =
            $this->entityIdentity(
                $entityType,
                $entityId,
            );

        try {
            $draft =
                $drafts->create(
                    entityType: $type,

                    entityId: $id,

                    requestedByAdminId: $this->actorId(
                        $request,
                    ),
                );

            $generation->execute(
                $draft,
            );
        } catch (
            AiSeoProviderException
            |AiSeoSuggestionValidationException
            |DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'ai_seo' => $exception
                    ->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'Fresh AI SEO suggestions generated.',
        );
    }

    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function enrichAnalysis(
        Request $request,
        string $entityType,
        string $entityId,
        string $analysisId,
        AiSeoAnalysisEnrichmentService $enrichment,
    ): RedirectResponse {
        [
            $type,
            $id,
        ] =
            $this->entityIdentity(
                $entityType,
                $entityId,
            );

        if (
            ! ctype_digit(
                $analysisId,
            )
            || (int) $analysisId < 1
        ) {
            abort(
                404,
            );
        }

        $analysis =
            AiSeoAnalysis::query()
                ->whereKey(
                    (int) $analysisId,
                )
                ->where(
                    'entity_type',
                    $type->value,
                )
                ->where(
                    'entity_id',
                    $id,
                )
                ->firstOrFail();

        try {
            $enrichment->enrich(
                analysisId: $analysis->id,

                enrichedByAdminId: $this->actorId(
                    $request,
                ),
            );
        } catch (
            AiSeoProviderException
            |AiSeoAnalysisEnrichmentValidationException
            |DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'ai_seo' => $exception
                    ->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'AI analysis explanation generated.',
        );
    }

    /**
     * @return array{0: AiSeoEntityType, 1: int}
     */
    private function entityIdentity(
        string $entityType,
        string $entityId,
    ): array {
        $type =
            AiSeoEntityType::tryFrom(
                $entityType,
            );

        if (
            $type === null
            || ! ctype_digit(
                $entityId,
            )
            || (int) $entityId < 1
        ) {
            abort(
                404,
            );
        }

        return [
            $type,
            (int) $entityId,
        ];
    }

    private function actorId(
        Request $request,
    ): ?int {
        $identifier =
            $request
                ->user()
                ?->getAuthIdentifier();

        if (
            is_int(
                $identifier,
            )
        ) {
            return $identifier;
        }

        if (
            is_string(
                $identifier,
            )
            && ctype_digit(
                $identifier,
            )
        ) {
            return (int) $identifier;
        }

        return null;
    }
}
