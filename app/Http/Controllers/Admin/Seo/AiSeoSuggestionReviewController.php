<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionReviewService;
use App\Domain\Seo\AiGovernance\Services\SeoAiLegacySuggestionGovernance;
use App\Models\Admin;
use App\Models\AiSeoSuggestion;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use LogicException;

final class AiSeoSuggestionReviewController
{
    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function approve(
        Request $request,
        string $suggestionId,
        AiSeoSuggestionReviewService $reviews,
        SeoAiLegacySuggestionGovernance $governance,
    ): RedirectResponse {
        $validated =
            $request->validate([
                'note' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

        $suggestion =
            $this->suggestion(
                $suggestionId,
            );

        $reviewer =
            $this->actor(
                $request,
            );

        try {
            DB::transaction(
                function () use (
                    $reviews,
                    $governance,
                    $suggestion,
                    $reviewer,
                    $validated,
                ): void {
                    $reviews->approve(
                        suggestion: $suggestion,

                        reviewedByAdminId: $this->actorId(
                            $reviewer,
                        ),

                        note: $this->note(
                            $validated,
                        ),
                    );

                    $governance->approve(
                        $suggestion,
                        $reviewer,
                    );
                },
            );
        } catch (
            DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'suggestion' => $exception
                    ->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'AI SEO suggestion approved for later application.',
        );
    }

    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function reject(
        Request $request,
        string $suggestionId,
        AiSeoSuggestionReviewService $reviews,
        SeoAiLegacySuggestionGovernance $governance,
    ): RedirectResponse {
        $validated =
            $request->validate([
                'note' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

        $suggestion =
            $this->suggestion(
                $suggestionId,
            );

        $reviewer =
            $this->actor(
                $request,
            );

        DB::transaction(
            function () use (
                $reviews,
                $governance,
                $suggestion,
                $reviewer,
                $validated,
            ): void {
                $reviews->reject(
                    suggestion: $suggestion,

                    reviewedByAdminId: $this->actorId(
                        $reviewer,
                    ),

                    note: $this->note(
                        $validated,
                    ),
                );

                $governance->reject(
                    $suggestion,
                    $reviewer,
                );
            },
        );

        return back()->with(
            'success',
            'AI SEO suggestion rejected.',
        );
    }

    private function suggestion(
        string $suggestionId,
    ): AiSeoSuggestion {
        if (
            ! ctype_digit(
                $suggestionId,
            )
            || (int) $suggestionId < 1
        ) {
            abort(
                404,
            );
        }

        return AiSeoSuggestion::query()
            ->findOrFail(
                (int) $suggestionId,
            );
    }

    private function actor(
        Request $request,
    ): Admin {
        $actor =
            $request->user();

        if (! $actor instanceof Admin) {
            abort(
                403,
            );
        }

        return $actor;
    }

    private function actorId(
        Admin $actor,
    ): int {
        $identifier =
            $actor->getKey();

        if (
            is_int(
                $identifier,
            )
            && $identifier > 0
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
            && (int) $identifier > 0
        ) {
            return (int) $identifier;
        }

        throw new LogicException(
            'Authenticated admin has an invalid primary key.',
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function note(
        array $validated,
    ): ?string {
        $note =
            $validated[
                'note'
            ]
            ?? null;

        return is_string(
            $note,
        )
            ? $note
            : null;
    }
}
