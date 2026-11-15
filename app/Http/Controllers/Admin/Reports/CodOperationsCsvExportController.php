<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminCodOperationsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\CodOperationsReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CodOperationsCsvExportController extends Controller
{
    private const MAX_EXPORT_ROWS = 10_000;

    public function __invoke(
        CodOperationsReportRequest $request,
        ReportDateRangeFactory $dateRangeFactory,
        GetAdminCodOperationsReportQuery $query,
    ): StreamedResponse {
        $validated =
            $request->validated();

        $preset =
            ReportDatePreset::tryFrom(
                (string) (
                    $validated['preset'] ??
                    ReportDatePreset::Last30Days->value
                ),
            ) ??
            ReportDatePreset::Last30Days;

        $range =
            $dateRangeFactory->make(
                preset: $preset,

                from: isset(
                    $validated['from'],
                )
                ? (string) $validated['from']
                : null,

                to: isset(
                    $validated['to'],
                )
                ? (string) $validated['to']
                : null,
            );

        $filters =
            new CodOperationsReportFiltersData(
                range: $range,

                statuses: $this->enumList(
                    $validated['status'] ??
                    [],
                    OrderStatus::class,
                ),

                fulfilmentStatuses: $this->enumList(
                    $validated['fulfilment_status'] ??
                    [],
                    FulfilmentStatus::class,
                ),

                paymentStatuses: $this->enumList(
                    $validated['payment_status'] ??
                    [],
                    PaymentStatus::class,
                ),

                search: isset(
                    $validated['search'],
                )
                ? (string) $validated['search']
                : null,

                /*
                 * Export in larger pages while retaining the
                 * same authoritative paginated query.
                 */
                perPage: 100,
            );

        $filename =
            'cod-operations-'.
            $range
                ->fromInclusive
                ->toDateString().
            '-'.
            $range
                ->toExclusive
                ->subDay()
                ->toDateString().
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
                 * UTF-8 BOM improves compatibility with Excel,
                 * especially for Bengali/customer text.
                 */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF",
                );

                fputcsv(
                    $handle,
                    [
                        'Order Number',
                        'Customer',
                        'Order Status',
                        'Fulfilment Status',
                        'Payment Method',
                        'Payment Status',
                        'Grand Total',
                        'Amount Due',
                        'Currency',
                        'Age Hours',
                        'Age Days',
                        'Collection Risk',
                        'Risk Severity',
                        'Risk Priority',
                        'Follow-up Required',
                        'Payment Due At',
                        'Paid At',
                        'Placed At',
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
                                $this->csvSafe(
                                    $row->orderNumber,
                                ),

                                $this->csvSafe(
                                    $row->customerName,
                                ),

                                $row->status,

                                $row->fulfilmentStatus,

                                $row->paymentMethod,

                                $row->paymentStatus,

                                $this->moneyForCsv(
                                    $row->grandTotal,
                                ),

                                $this->moneyForCsv(
                                    $row->amountDue,
                                ),

                                $row->currencyCode,

                                $row->ageHours,

                                $row->ageDays,

                                $row
                                    ->collectionRisk
                                    ->state
                                    ->value,

                                $row
                                    ->collectionRisk
                                    ->state
                                    ->severity(),

                                $row
                                    ->collectionRisk
                                    ->state
                                    ->priority(),

                                $row
                                    ->collectionRisk
                                    ->requiresFollowUp()
                                ? 'Yes'
                                : 'No',

                                $row->paymentDueAt,

                                $row->paidAt,

                                $row->placedAt,
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

    private function moneyForCsv(
        int $minorUnits,
    ): string {
        return number_format(
            $minorUnits / 100,
            2,
            '.',
            '',
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

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enumClass
     * @return array<int, T>
     */
    private function enumList(
        mixed $values,
        string $enumClass,
    ): array {
        if (
            ! is_array(
                $values,
            )
        ) {
            return [];
        }

        $result = [];

        foreach (
            $values as $value
        ) {
            if (
                ! is_string(
                    $value,
                )
            ) {
                continue;
            }

            $enum =
                $enumClass::tryFrom(
                    $value,
                );

            if (
                $enum !== null
            ) {
                $result[] =
                    $enum;
            }
        }

        return $result;
    }
}
