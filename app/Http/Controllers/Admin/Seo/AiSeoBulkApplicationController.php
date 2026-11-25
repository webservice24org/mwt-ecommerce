<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkApplicationService;
use App\Models\AiSeoBulkJob;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AiSeoBulkApplicationController
{
    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function store(
        Request $request,
        string $bulkJobId,
        AiSeoBulkApplicationService $applications,
    ): RedirectResponse {
        $job =
            $this->job(
                $bulkJobId,
            );

        $validated =
            $request->validate([
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

                'note' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

        /** @var list<int> $suggestionIds */
        $suggestionIds =
            $validated[
                'suggestion_ids'
            ];

        $note =
            $validated[
                'note'
            ]
            ?? null;

        try {
            $batch =
                $applications->apply(
                    job: $job,

                    suggestionIds: $suggestionIds,

                    appliedByAdminId: $this->actorId(
                        $request,
                    ),

                    note: is_string(
                        $note,
                    )
                            ? $note
                            : null,
                );
        } catch (
            DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'suggestions' => $exception
                    ->getMessage(),
            ]);
        }

        $summary =
            $batch
                ->summary;

        return back()->with(
            'success',
            sprintf(
                'Bulk SEO application completed: %d applied, %d already applied, %d conflicts, %d stale, %d not approved, %d failed.',
                $summary[
                    'applied'
                ] ?? 0,
                $summary[
                    'already_applied'
                ] ?? 0,
                $summary[
                    'conflict'
                ] ?? 0,
                $summary[
                    'stale'
                ] ?? 0,
                $summary[
                    'not_approved'
                ] ?? 0,
                $summary[
                    'failed'
                ] ?? 0,
            ),
        );
    }

    private function job(
        string $bulkJobId,
    ): AiSeoBulkJob {
        if (
            ! ctype_digit(
                $bulkJobId,
            )
            || (int) $bulkJobId < 1
        ) {
            abort(
                404,
            );
        }

        return AiSeoBulkJob::query()
            ->findOrFail(
                (int) $bulkJobId,
            );
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
