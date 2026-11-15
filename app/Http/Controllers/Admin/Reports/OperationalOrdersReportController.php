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
use Inertia\Inertia;
use Inertia\Response;

final class OperationalOrdersReportController extends Controller
{
    public function __invoke(
        OperationalOrdersReportRequest $request,
        ReportDateRangeFactory $dateRangeFactory,
        GetAdminOperationalOrdersReportQuery $query,
    ): Response {
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
            'Admin/Reports/OperationalOrders/Index',
            [
                'filters' => [
                    'preset' => $range
                        ->preset
                        ->value,

                    'from' => $range
                        ->fromInclusive
                        ->toDateString(),

                    'to' => $range
                        ->toExclusive
                        ->subDay()
                        ->toDateString(),

                    'statuses' => $filters
                        ->statusValues(),

                    'fulfilment_statuses' => $filters
                        ->fulfilmentStatusValues(),

                    'payment_statuses' => $filters
                        ->paymentStatusValues(),

                    'payment_methods' => $filters
                        ->paymentMethodValues(),

                    'search' => $filters
                        ->normalizedSearch(),

                    'per_page' => $filters
                        ->normalizedPerPage(),
                ],

                'summary' => $result
                    ->summary
                    ->toArray(),

                'rows' => array_map(
                    static fn (
                        $row,
                    ): array => $row->toArray(),

                    $result->rows,
                ),

                'pagination' => $result
                    ->pagination
                    ->toArray(),

                'options' => [
                    'presets' => $this->enumOptions(
                        ReportDatePreset::cases(),
                    ),

                    'statuses' => $this->enumOptions(
                        OrderStatus::cases(),
                    ),

                    'fulfilment_statuses' => $this->enumOptions(
                        FulfilmentStatus::cases(),
                    ),

                    'payment_statuses' => $this->enumOptions(
                        PaymentStatus::cases(),
                    ),

                    'payment_methods' => $this->enumOptions(
                        PaymentMethod::cases(),
                    ),
                ],
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

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value:string,label:string}>
     */
    private function enumOptions(
        array $cases,
    ): array {
        return array_map(
            static function (\BackedEnum $case): array {
                $value =
                    (string) $case->value;

                $label =
                    method_exists(
                        $case,
                        'label',
                    )
                    ? (string) $case->label()
                    : str(
                        $value,
                    )
                        ->replace(
                            '_',
                            ' ',
                        )
                        ->title()
                        ->toString();

                return [
                    'value' => $value,

                    'label' => $label,
                ];
            },
            $cases,
        );
    }
}
