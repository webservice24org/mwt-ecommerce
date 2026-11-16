<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Reporting\Data\InventoryOperationsReportFiltersData;
use App\Domain\Reporting\Queries\GetAdminInventoryOperationsReportQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\InventoryOperationsReportRequest;
use BackedEnum;
use Inertia\Inertia;
use Inertia\Response;
use UnitEnum;

final class InventoryOperationsReportController extends Controller
{
    public function __invoke(
        InventoryOperationsReportRequest $request,
        GetAdminInventoryOperationsReportQuery $query,
    ): Response {
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

                perPage: isset(
                    $validated['per_page'],
                )
                ? (int) $validated['per_page']
                : 25,
            );

        $result =
            $query->get(
                filters: $filters,

                page: isset(
                    $validated['page'],
                )
                ? max(
                    1,
                    (int) $validated['page'],
                )
                : 1,
            );

        return Inertia::render(
            'Admin/Reports/InventoryOperations/Index',
            [
                'filters' => [
                    'statuses' => $filters->statusValues(),

                    'stock_states' => $filters->stockStateValues(),

                    'search' => $filters->normalizedSearch(),

                    'per_page' => $filters->normalizedPerPage(),
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
                'statuses' => $this->enumOptions(
                    ProductStatus::cases(),
                ),

                'stock_states' => [
                [
                    'value' => 'in_stock',

                    'label' => 'In stock',
                ],

                [
                    'value' => 'low_stock',

                    'label' => 'Low stock',
                ],

                [
                    'value' => 'out_of_stock',

                    'label' => 'Out of stock',
                ],

                [
                    'value' => 'negative_stock',

                    'label' => 'Negative stock',
                ],
                    ],
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

    /**
     * @param  array<int, UnitEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(
        array $cases,
    ): array {
        return array_map(
            static function (UnitEnum $case): array {
                $value =
                    $case instanceof BackedEnum
                    ? (string) $case->value
                    : $case->name;

                $label =
                    method_exists(
                        $case,
                        'label',
                    )
                    ? (string) $case->label()
                    : str(
                        $case->name,
                    )
                        ->headline()
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
