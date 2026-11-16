<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reports;

use App\Domain\Catalog\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InventoryOperationsReportRequest extends FormRequest
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
            'status' => [
                'sometimes',
                'array',
            ],

            'status.*' => [
                Rule::enum(
                    ProductStatus::class,
                ),
            ],

            'stock_state' => [
                'sometimes',
                'array',
            ],

            'stock_state.*' => [
                'string',
                Rule::in([
                    'in_stock',
                    'low_stock',
                    'out_of_stock',
                    'negative_stock',
                ]),
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
        ];
    }
}
