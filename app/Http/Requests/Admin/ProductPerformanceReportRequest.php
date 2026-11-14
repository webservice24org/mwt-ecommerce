<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Data\ProductPerformanceReportFiltersData;
use App\Domain\Reporting\Enums\ReportDatePreset;
use App\Domain\Reporting\Services\ReportDateRangeFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductPerformanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => [
                'nullable',
                'string',
                Rule::enum(
                    ReportDatePreset::class,
                ),
            ],

            'from' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:preset,custom',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:preset,custom',
                'after_or_equal:from',
            ],

            'timezone' => [
                'nullable',
                'string',
                'timezone',
            ],

            'status' => [
                'nullable',
                'array',
            ],

            'status.*' => [
                'string',
                Rule::enum(
                    OrderStatus::class,
                ),
            ],

            'payment_status' => [
                'nullable',
                'array',
            ],

            'payment_status.*' => [
                'string',
                Rule::enum(
                    PaymentStatus::class,
                ),
            ],

            'search' => [
                'nullable',
                'string',
                'max:120',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:10',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function preset(): ReportDatePreset
    {
        $preset =
            $this->optionalString(
                'preset',
            );

        return $preset !== null
            ? ReportDatePreset::from(
                $preset,
            )
            : ReportDatePreset::ThisMonth;
    }

    public function fromDate(): ?string
    {
        return $this->optionalString(
            'from',
        );
    }

    public function toDate(): ?string
    {
        return $this->optionalString(
            'to',
        );
    }

    public function timezone(): ?string
    {
        return $this->optionalString(
            'timezone',
        );
    }

    /**
     * @return list<OrderStatus>
     */
    public function statuses(): array
    {
        $values =
            $this->validatedArray(
                'status',
            );

        return array_map(
            static fn (
                string $value,
            ): OrderStatus => OrderStatus::from(
                $value,
            ),
            $values,
        );
    }

    /**
     * @return list<PaymentStatus>
     */
    public function paymentStatuses(): array
    {
        $values =
            $this->validatedArray(
                'payment_status',
            );

        return array_map(
            static fn (
                string $value,
            ): PaymentStatus => PaymentStatus::from(
                $value,
            ),
            $values,
        );
    }

    public function searchTerm(): ?string
    {
        return $this->optionalString(
            'search',
        );
    }

    public function perPage(): int
    {
        $value =
            $this->validated(
                'per_page',
                25,
            );

        return is_numeric(
            $value,
        )
            ? (int) $value
            : 25;
    }

    public function pageNumber(): int
    {
        $value =
            $this->validated(
                'page',
                1,
            );

        return is_numeric(
            $value,
        )
            ? max(
                1,
                (int) $value,
            )
            : 1;
    }

    public function filters(
        ReportDateRangeFactory $dateRanges,
    ): ProductPerformanceReportFiltersData {
        return new ProductPerformanceReportFiltersData(
            range: $dateRanges->make(
                preset: $this->preset(),

                from: $this->fromDate(),

                to: $this->toDate(),

                timezone: $this->timezone(),
            ),

            statuses: $this->statuses(),

            paymentStatuses: $this->paymentStatuses(),

            search: $this->searchTerm(),

            perPage: $this->perPage(),
        );
    }

    /**
     * @return array{
     *     preset: string,
     *     from?: string,
     *     to?: string,
     *     timezone?: string,
     *     status?: list<string>,
     *     payment_status?: list<string>,
     *     search?: string,
     *     per_page: int
     * }
     */
    public function queryParameters(): array
    {
        /** @var array{
         *     preset: string,
         *     from?: string,
         *     to?: string,
         *     timezone?: string,
         *     status?: list<string>,
         *     payment_status?: list<string>,
         *     search?: string,
         *     per_page: int
         * } $query
         */
        $query = [
            'preset' => $this->preset()->value,

            'per_page' => $this->perPage(),
        ];

        if (
            $this->preset() ===
            ReportDatePreset::Custom
        ) {
            $from =
                $this->fromDate();

            $to =
                $this->toDate();

            if (
                $from !== null
            ) {
                $query['from'] =
                    $from;
            }

            if (
                $to !== null
            ) {
                $query['to'] =
                    $to;
            }
        }

        $timezone =
            $this->timezone();

        if (
            $timezone !== null
        ) {
            $query['timezone'] =
                $timezone;
        }

        $statuses =
            array_map(
                static fn (
                    OrderStatus $status,
                ): string => $status->value,
                $this->statuses(),
            );

        if (
            $statuses !== []
        ) {
            $query['status'] =
                $statuses;
        }

        $paymentStatuses =
            array_map(
                static fn (
                    PaymentStatus $status,
                ): string => $status->value,
                $this->paymentStatuses(),
            );

        if (
            $paymentStatuses !== []
        ) {
            $query[
                'payment_status'
            ] =
                $paymentStatuses;
        }

        $search =
            $this->searchTerm();

        if (
            $search !== null
        ) {
            $query['search'] =
                $search;
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function validatedArray(
        string $key,
    ): array {
        $value =
            $this->validated(
                $key,
                [],
            );

        if (
            ! is_array(
                $value,
            )
        ) {
            return [];
        }

        $values = [];

        foreach (
            $value as $item
        ) {
            if (
                is_string(
                    $item,
                )
            ) {
                $values[] =
                    $item;
            }
        }

        return $values;
    }

    private function optionalString(
        string $key,
    ): ?string {
        $value =
            $this->validated(
                $key,
                null,
            );

        if (
            ! is_string(
                $value,
            )
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }
}
