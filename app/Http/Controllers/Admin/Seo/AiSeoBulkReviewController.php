<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkSuggestionReviewService;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\AiSeoBulkJob;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AiSeoBulkReviewController
{
    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function store(
        Request $request,
        string $bulkJobId,
        AiSeoBulkSuggestionReviewService $reviews,
    ): RedirectResponse {
        $job = $this->job($bulkJobId);

        $validated = $request->validate([
            'suggestion_ids' => [
                'required',
                'array',
                'min:1',
                'max:250',
            ],
            'suggestion_ids.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'decision' => [
                'required',
                'string',
                'in:approved,rejected',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $decision = AiSeoSuggestionReviewDecision::from(
            (string) $validated['decision'],
        );

        /** @var list<int> $suggestionIds */
        $suggestionIds = $validated['suggestion_ids'];

        $note = $validated['note'] ?? null;

        try {
            $batch = $reviews->review(
                job: $job,
                suggestionIds: $suggestionIds,
                decision: $decision,
                reviewedByAdminId: $this->actorId($request),
                note: is_string($note) ? $note : null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'suggestions' => $exception->getMessage(),
            ]);
        }

        $summary = $batch->summary;

        return back()->with(
            'success',
            sprintf(
                'Bulk review completed: %d approved, %d rejected, %d already decided, %d stale blocked, %d failed.',
                $summary['approved'] ?? 0,
                $summary['rejected'] ?? 0,
                $summary['already_decided'] ?? 0,
                $summary['blocked_stale'] ?? 0,
                $summary['failed'] ?? 0,
            ),
        );
    }

    private function job(string $bulkJobId): AiSeoBulkJob
    {
        if (
            ! ctype_digit($bulkJobId)
            || (int) $bulkJobId < 1
        ) {
            abort(404);
        }

        return AiSeoBulkJob::query()->findOrFail((int) $bulkJobId);
    }

    private function actorId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (is_int($identifier)) {
            return $identifier;
        }

        if (
            is_string($identifier)
            && ctype_digit($identifier)
        ) {
            return (int) $identifier;
        }

        return null;
    }
}
