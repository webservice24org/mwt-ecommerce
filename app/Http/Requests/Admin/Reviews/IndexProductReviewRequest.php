<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reviews;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProductReviewRequest extends FormRequest
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
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    ProductReviewStatus::class,
                ),
            ],

            'rating' => [
                'nullable',
                'integer',
                'between:1,5',
            ],

            'verified' => [
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
