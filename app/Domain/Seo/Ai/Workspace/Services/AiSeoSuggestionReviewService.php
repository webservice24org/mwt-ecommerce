<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Workspace\Services;

use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionReview;
use DomainException;
use Illuminate\Support\Facades\DB;
use JsonException;

final readonly class AiSeoSuggestionReviewService
{
    public function __construct(
        private AiSeoSuggestionFreshnessChecker $freshness,
    ) {}

    /**
     * @throws JsonException
     */
    public function approve(
        AiSeoSuggestion $suggestion,
        ?int $reviewedByAdminId = null,
        ?string $note = null,
    ): AiSeoSuggestionReview {
        return $this->review(
            suggestion: $suggestion,

            decision: AiSeoSuggestionReviewDecision::Approved,

            reviewedByAdminId: $reviewedByAdminId,

            note: $note,
        );
    }

    /**
     * @throws JsonException
     */
    public function reject(
        AiSeoSuggestion $suggestion,
        ?int $reviewedByAdminId = null,
        ?string $note = null,
    ): AiSeoSuggestionReview {
        return $this->review(
            suggestion: $suggestion,

            decision: AiSeoSuggestionReviewDecision::Rejected,

            reviewedByAdminId: $reviewedByAdminId,

            note: $note,
        );
    }

    /**
     * @throws JsonException
     */
    private function review(
        AiSeoSuggestion $suggestion,
        AiSeoSuggestionReviewDecision $decision,
        ?int $reviewedByAdminId,
        ?string $note,
    ): AiSeoSuggestionReview {
        return DB::transaction(
            function () use (
                $suggestion,
                $decision,
                $reviewedByAdminId,
                $note,
            ): AiSeoSuggestionReview {
                $locked =
                    AiSeoSuggestion::query()
                        ->with(
                            'request',
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $suggestion->id,
                        );

                $request =
                    $locked
                        ->request;

                $freshness =
                    $this
                        ->freshness
                        ->check(
                            $request,
                        );

                if (
                    $decision ===
                        AiSeoSuggestionReviewDecision::Approved
                    && $freshness
                        ->isStale
                ) {
                    throw new DomainException(
                        'This AI SEO suggestion is stale because the entity changed after generation. Generate fresh suggestions before approval.',
                    );
                }

                $now =
                    now();

                return AiSeoSuggestionReview::query()
                    ->create([
                        'ai_seo_suggestion_id' => $locked
                            ->id,

                        'decision' => $decision,

                        'reviewed_by_admin_id' => $reviewedByAdminId,

                        'note' => $this->nullableNote(
                            $note,
                        ),

                        'source_snapshot_hash' => $freshness
                            ->sourceSnapshotHash,

                        'current_snapshot_hash' => $freshness
                            ->currentSnapshotHash,

                        'freshness_state' => $freshness
                            ->state,

                        'reviewed_at' => $now,

                        'created_at' => $now,
                    ]);
            },
        );
    }

    private function nullableNote(
        ?string $note,
    ): ?string {
        if ($note === null) {
            return null;
        }

        $note =
            trim(
                $note,
            );

        if ($note === '') {
            return null;
        }

        return mb_substr(
            $note,
            0,
            1000,
        );
    }
}
