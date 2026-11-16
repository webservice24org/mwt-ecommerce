<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Operations\OperationalCsvFormatter;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\InventoryOperationsReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryOperationsCsvExportController extends Controller
{
    public function __invoke(
        InventoryOperationsReportRequest $request,
        GetAdminInventoryOperationsReportQuery $query,
        OperationalExportRegistry $exports,
        OperationalCsvFormatter $csv,
    ): StreamedResponse {
        $validated =
            $request->validated();

        $definition =
            $exports->get(
                OperationalExportType::InventoryOperations,
            );

        $filters =
            new InventoryOperationsReportFiltersData(
                statuses: $this->stringList(
                    $validated['status'] ??
                    [],
                ),

                stockStates: $this->stringList(
                    $validated['stock_state'] ??
                    [],
                ),

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
                        $result->rows as $row
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
                                    $row->productId,
                                    $row->name,
                                    $row->sku,
                                    $row->slug,
                                    $row->status,

                                    $row->trackInventory
                                    ? 'Yes'
                                    : 'No',

                                    $row->stockQuantity,
                                    $row->lowStockThreshold,
                                    $row->stockState,

                                    $row
                                        ->alert
                                        ->state
                                        ->value,

                                    $row
                                        ->alert
                                        ->state
                                        ->label(),

                                    $row
                                        ->alert
                                        ->state
                                        ->severity(),

                                    $row
                                        ->alert
                                        ->state
                                        ->priority(),

                                    $row
                                        ->alert
                                        ->requiresAttention()
                                    ? 'Yes'
                                    : 'No',

                                    $row->updatedAt,
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
