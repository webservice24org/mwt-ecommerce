<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AiSeoBulkPlanController
{
    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function store(
        Request $request,
        AiSeoBulkPlanService $plans,
    ): RedirectResponse {
        $validated =
            $request->validate([
                'targets' => [
                    'required',
                    'array',
                    'min:1',
                    'max:250',
                ],

                'targets.*.entity_type' => [
                    'required',
                    'string',
                    'in:product,category,brand,page',
                ],

                'targets.*.entity_id' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'filters' => [
                    'sometimes',
                    'array',
                ],
            ]);

        /** @var list<array{entity_type: string, entity_id: int}> $targets */
        $targets =
            $validated[
                'targets'
            ];

        /** @var array<string, mixed> $filters */
        $filters =
            $validated[
                'filters'
            ]
            ?? [];

        try {
            $job =
                $plans->create(
                    targets: $targets,

                    filters: $filters,

                    plannedByAdminId: $this->actorId(
                        $request,
                    ),
                );
        } catch (
            DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'targets' => $exception
                    ->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.seo.bulk.plans.show',
                [
                    'bulkJobId' => $job->id,
                ],
            )
            ->with(
                'success',
                sprintf(
                    'Bulk AI plan %s created. Review the plan before queueing execution.',
                    $job
                        ->reference,
                ),
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
