<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalCsvFormatter;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Queries\GetAdminActionQueueQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ActionQueueReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActionQueueCsvExportController extends Controller
{
    public function __invoke(
        ActionQueueReportRequest $request,
        GetAdminActionQueueQuery $query,
        OperationalExportRegistry $exports,
        OperationalCsvFormatter $csv,
    ): StreamedResponse {
        $validated =
            $request->validated();

        $definition =
            $exports->get(
                OperationalExportType::ActionQueue,
            );

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
                ? (bool) $validated['requires_action']
                : null,

                search: isset(
                    $validated['search'],
                )
                ? (string) $validated['search']
                : null,

                perPage: 100,
            );

        return response()->streamDownload(
            function () use ($definition, $filters, $query, $csv): void {
                $handle =
                    fopen(
                        'php://output',
                        'wb',
                    );

                if (
                    $handle === false
                ) {
                    return;
                }

                $bom =
                    $csv->bom();

                if (
                    $bom !== ''
                ) {
                    fwrite(
                        $handle,
                        $bom,
                    );
                }

                fputcsv(
                    $handle,
                    $definition->headers,
                );

                $page =
                    1;

                $exported =
                    0;

                do {
                    $result =
                        $query->get(
                            filters: $filters,

                            page: $page,
                        );

                    foreach (
                        $result->items as $item
                    ) {
                        if (
                            $exported >=
                            $definition->maxRows
                        ) {
                            break 2;
                        }

                        fputcsv(
                            $handle,
                            $csv->safeRow(
                                [
                                    $item
                                        ->source
                                        ->label(),

                                    $item->entityType,
                                    $item->entityId,
                                    $item->reference,
                                    $item->title,
                                    $item->priority,

                                    $item
                                        ->severity
                                        ->value,

                                    $item->reason,

                                    $item->requiresAction
                                    ? 'Yes'
                                    : 'No',

                                    $item->ageHours,
                                    $item->ageDays,
                                    $item->actionLabel,
                                    $item->actionUrl,
                                ],
                            ),
                        );

                        $exported++;
                    }

                    $page++;
                } while (
                    $page <=
                    $result
                        ->pagination
                        ->lastPage
                );

                fclose(
                    $handle,
                );
            },
            $definition->filename(),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Cache-Control' => 'no-store, no-cache, must-revalidate',
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
                array_map(
                    static fn (
                        mixed $value,
                    ): string => is_string(
                        $value,
                    )
                        ? $value
                        : '',

                    $values,
                ),
                static fn (
                    string $value,
                ): bool => $value !== '',
            ),
        );
    }
}
