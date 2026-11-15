<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\OperationalOrdersReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Queries\GetAdminOperationalOrdersReportQuery;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\OperationalOrdersReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OperationalOrdersCsvExportController extends Controller
{
    private const MAX_EXPORT_ROWS =
        10_000;

    public function __invoke(
        OperationalOrdersReportRequest $request,
        GetAdminOperationalOrdersReportQuery $query,
    ): StreamedResponse {
        $validated =
            $request->validated();

        /*
         * Use the exact same date-range construction mechanism
         * already used by OperationalOrdersReportController.
         *
         * Copy the existing working date-range code from that
         * controller here rather than creating a second policy.
         */
        $range =
            app(
                ReportDateRangeFactory::class,
            )->make(
                preset: ReportDatePreset::tryFrom(
                    (string) (
                        $validated['preset'] ??
                        ReportDatePreset::Last30Days->value
                    ),
                ) ??
                ReportDatePreset::Last30Days,

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
            function () use ($query, $filters): void {
                $handle =
                    fopen(
                        'php://output',
                        'wb',
                    );

                if (
                    $handle ===
                    false
                ) {
                    return;
                }

                /*
                 * UTF-8 BOM helps Excel display customer/order
                 * text correctly.
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
                        'Payment Status',
                        'Payment Method',
                        'Grand Total',
                        'Amount Due',
                        'Currency',
                        'Age Hours',
                        'Age Days',
                        'Attention State',
                        'Attention Severity',
                        'Attention Required',
                        'Payment Due At',
                        'Paid At',
                        'Placed At',
                    ],
                );

                $exported =
                    0;

                $page =
                    1;

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
                                    $row
                                        ->orderNumber,
                                ),

                                $this->csvSafe(
                                    $row
                                        ->customerName,
                                ),

                                $row
                                    ->status,

                                $row
                                    ->fulfilmentStatus,

                                $row
                                    ->paymentStatus,

                                $row
                                    ->paymentMethod,

                                $this->moneyForCsv(
                                    $row
                                        ->grandTotal,
                                ),

                                $this->moneyForCsv(
                                    $row
                                        ->amountDue,
                                ),

                                $row
                                    ->currencyCode,

                                $row
                                    ->ageHours,

                                $row
                                    ->ageDays,

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
                                    ->requiresAttention()
                                ? 'Yes'
                                : 'No',

                                $row
                                    ->paymentDueAt,

                                $row
                                    ->paidAt,

                                $row
                                    ->placedAt,
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
            'operational-orders-'.$range
                ->fromInclusive
                ->format(
                    'Y-m-d',
                ).'-'.$range
                ->toExclusive
                ->subDay()
                ->format(
                    'Y-m-d',
                ).'.csv',
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
            $trimmed ===
            ''
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
