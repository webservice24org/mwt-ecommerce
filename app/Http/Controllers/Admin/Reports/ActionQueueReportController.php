<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;
use App\Domain\Reporting\Queries\GetAdminActionQueueQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ActionQueueReportRequest;
use Inertia\Inertia;
use Inertia\Response;

final class ActionQueueReportController extends Controller
{
    public function __invoke(
        ActionQueueReportRequest $request,
        GetAdminActionQueueQuery $query,
    ): Response {
        $validated =
            $request->validated();

        $filters =
            new ActionQueueFiltersData(
                sources: $this->stringList(
                    $validated['source'] ??
                    [],
                ),

                severities: $this->stringList(
                    $validated['severity'] ??
                    [],
                ),

                requiresAction: array_key_exists(
                    'requires_action',
                    $validated,
                )
                ? filter_var(
                    $validated[
                        'requires_action'
                    ],
                    FILTER_VALIDATE_BOOLEAN,
                )
                : null,

                search: isset(
                    $validated['search'],
                )
                ? (string) $validated['search']
                : null,

                perPage: (int) (
                    $validated['per_page'] ??
                    25
                ),
            );

        $result =
            $query->get(
                filters: $filters,

                page: (int) (
                    $validated['page'] ??
                    1
                ),
            );

        return Inertia::render(
            'Admin/Reports/ActionQueue/Index',
            [
                'filters' => $filters->toArray(),

                'items' => array_map(
                    static fn (
                        $item,
                    ): array => $item->toArray(),

                    $result->items,
                ),

                'pagination' => $result
                    ->pagination
                    ->toArray(),

                'options' => [
                'sources' => array_map(
                    static fn (
                        ActionQueueSource $source,
                    ): array => [
                        'value' => $source->value,

                        'label' => $source->label(),
                    ],
                    ActionQueueSource::cases(),
                ),

                'severities' => array_map(
                    static fn (
                        ActionQueueSeverity $severity,
                    ): array => [
                        'value' => $severity->value,

                        'label' => str(
                            $severity->value,
                        )
                            ->headline()
                            ->toString(),
                    ],
                    ActionQueueSeverity::cases(),
                ),
                ],
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function stringList(
        mixed $values,
    ): array {
        if (
            ! is_array(
                $values,
            )
        ) {
            return [];
        }

        return array_values(
            array_filter(
                $values,
                static fn (
                    mixed $value,
                ): bool => is_string(
                    $value,
                ) &&
                    $value !== '',
            ),
        );
    }
}
