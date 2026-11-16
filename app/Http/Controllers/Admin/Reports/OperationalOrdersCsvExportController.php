<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\OperationalOrdersReportFiltersData;
use App\Domain\Reporting\Enums\OperationalExportType;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Operations\OperationalCsvFormatter;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Queries\GetAdminOperationalOrdersReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\OperationalOrdersReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OperationalOrdersCsvExportController extends Controller
{
    public function __invoke(
        OperationalOrdersReportRequest $request,
        ReportDateRangeFactory $dateRangeFactory,
        GetAdminOperationalOrdersReportQuery $query,
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
                OperationalExportType::OperationalOrders,
            );

        $filters =
            new OperationalOrdersReportFiltersData(
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

                paymentMethods: $this->enumList(
                    $validated['payment_method'] ??
                    [],
                    PaymentMethod::class,
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
                                    $row->fulfilmentStatus,

                                    $row->grandTotal,
                                    $row->amountDue,
                                    $row->currencyCode,

                                    $row->ageHours,
                                    $row->ageDays,

                                    $row
                                        ->attention
                                        ->state
                                        ->value,

                                    $row
                                        ->attention
                                        ->state
                                        ->severity(),

                                    $row
                                        ->attention
                                        ->state
                                        ->priority(),

                                    $row
                                        ->attention
                                        ->requiresAttention()
                                    ? 'Yes'
                                    : 'No',

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
