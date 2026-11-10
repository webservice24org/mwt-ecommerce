<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'scheduled',
                    'expired',
                ]),
            ],

            'discount_type' => [
                'nullable',
                Rule::enum(
                    DiscountType::class,
                ),
            ],

            'stackable' => [
                'nullable',
                Rule::in([
                    'yes',
                    'no',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    20,
                    50,
                    100,
                ]),
            ],
        ];
    }
}
