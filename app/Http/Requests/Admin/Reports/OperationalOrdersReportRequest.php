<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reports;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Reporting\Enums\ReportDatePreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OperationalOrdersReportRequest extends FormRequest
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
                'date',
                'required_if:preset,custom',
            ],

            'to' => [
                'nullable',
                'date',
                'required_if:preset,custom',
                'after_or_equal:from',
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

            'fulfilment_status' => [
                'nullable',
                'array',
            ],

            'fulfilment_status.*' => [
                'string',
                Rule::enum(
                    FulfilmentStatus::class,
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

            'payment_method' => [
                'nullable',
                'array',
            ],

            'payment_method.*' => [
                'string',
                Rule::enum(
                    PaymentMethod::class,
                ),
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    25,
                    50,
                    100,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'attention' => [
                'nullable',
                'string',
                Rule::in([
                    'all',
                    'required',
                    'critical',
                    'normal',
                ]),
            ],
        ];
    }
}
