<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Queries\GetAdminActionQueueQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ActionQueueReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActionQueueCsvExportController extends Controller
{
    private const MAX_EXPORT_ROWS = 10_000;

    public function __invoke(
        ActionQueueReportRequest $request,
        GetAdminActionQueueQuery $query,
    ): StreamedResponse {
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

                perPage: 100,
            );

        $filename =
            'admin-action-queue-'.
            now()->format(
                'Y-m-d-His',
            ).
            '.csv';

        return response()->streamDownload(
            function () use ($query, $filters): void {
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

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF",
                );

                fputcsv(
                    $handle,
                    [
                        'Source',
                        'Entity Type',
                        'Entity ID',
                        'Reference',
                        'Title',
                        'Priority',
                        'Severity',
                        'Reason',
                        'Requires Action',
                        'Age Hours',
                        'Age Days',
                        'Action Label',
                        'Action URL',
                    ],
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
                            self::MAX_EXPORT_ROWS
                        ) {
                            break 2;
                        }

                        fputcsv(
                            $handle,
                            [
                                $this->csvSafe(
                                    $item
                                        ->source
                                        ->label(),
                                ),

                                $this->csvSafe(
                                    $item->entityType,
                                ),

                                $item->entityId,

                                $this->csvSafe(
                                    $item->reference,
                                ),

                                $this->csvSafe(
                                    $item->title,
                                ),

                                $item->priority,

                                $item
                                    ->severity
                                    ->value,

                                $this->csvSafe(
                                    $item->reason,
                                ),

                                $item->requiresAction
                                ? 'Yes'
                                : 'No',

                                $item->ageHours,

                                $item->ageDays,

                                $this->csvSafe(
                                    $item->actionLabel,
                                ),

                                $this->csvSafe(
                                    $item->actionUrl,
                                ),
                            ],
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
            $filename,
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

    private function csvSafe(
        string $value,
    ): string {
        $trimmed =
            ltrim(
                $value,
            );

        if (
            $trimmed === ''
        ) {
            return $value;
        }

        if (
            in_array(
                $trimmed[0],
                [
                    '=',
                    '+',
                    '-',
                    '@',
                ],
                true,
            )
        ) {
            return "'".$value;
        }

        return $value;
    }
}
