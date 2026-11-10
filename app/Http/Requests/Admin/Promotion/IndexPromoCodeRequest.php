<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Promotion;

use App\Domain\Promotion\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPromoCodeRequest extends FormRequest
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

            'promotion' => [
                'nullable',
                'string',
                'max:50',
            ],

            'discount_type' => [
                'nullable',
                Rule::enum(
                    DiscountType::class,
                ),
            ],

            'source' => [
                'nullable',
                Rule::in([
                    'standalone',
                    'inherited',
                    'override',
                ]),
            ],

            'usage' => [
                'nullable',
                Rule::in([
                    'unused',
                    'used',
                    'exhausted',
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
