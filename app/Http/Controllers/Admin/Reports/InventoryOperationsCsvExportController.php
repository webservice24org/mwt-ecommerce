<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\InventoryOperationsReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryOperationsCsvExportController extends Controller
{
    private const MAX_EXPORT_ROWS = 10_000;

    public function __invoke(
        InventoryOperationsReportRequest $request,
        GetAdminInventoryOperationsReportQuery $query,
    ): StreamedResponse {
        $validated =
            $request->validated();

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

                /*
                 * The query remains paginated internally.
                 * Export simply walks its pages.
                 */
                perPage: 100,
            );

        $filename =
            'inventory-operations-'.
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

                /*
                 * UTF-8 BOM helps Excel render Bengali and
                 * other UTF-8 product/customer data properly.
                 */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF",
                );

                fputcsv(
                    $handle,
                    [
                        'Product ID',
                        'Product Name',
                        'SKU',
                        'Slug',
                        'Product Status',
                        'Inventory Tracked',
                        'Stock Quantity',
                        'Low Stock Threshold',
                        'Stock State',
                        'Alert State',
                        'Alert Label',
                        'Alert Severity',
                        'Alert Priority',
                        'Requires Attention',
                        'Updated At',
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
                        $result->rows as $row
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
                                $row->productId,

                                $this->csvSafe(
                                    $row->name,
                                ),

                                $this->csvSafe(
                                    $row->sku ??
                                    '',
                                ),

                                $this->csvSafe(
                                    $row->slug,
                                ),

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

                                $this->csvSafe(
                                    $row
                                        ->alert
                                        ->state
                                        ->label(),
                                ),

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
