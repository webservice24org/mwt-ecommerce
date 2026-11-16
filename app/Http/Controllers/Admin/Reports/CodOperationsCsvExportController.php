<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\CodOperationsReportFiltersData;
use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Operations\OperationalCsvFormatter;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Queries\GetAdminCodOperationsReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\CodOperationsReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CodOperationsCsvExportController extends Controller
{
    public function __invoke(
        CodOperationsReportRequest $request,
        ReportDateRangeFactory $dateRangeFactory,
        GetAdminCodOperationsReportQuery $query,
        OperationalExportRegistry $exports,
        OperationalCsvFormatter $csv,
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

        $definition =
            $exports->get(
                OperationalExportType::CodOperations,
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
                                    $row->orderNumber,
                                    $row->customerName,
                                    $row->status,
                                    $row->paymentStatus,
                                    $row->paymentMethod,
                                    $row->grandTotal,
                                    $row->amountDue,
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

        $result =
            [];

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
