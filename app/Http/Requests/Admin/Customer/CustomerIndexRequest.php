<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Customer;

use App\Domain\Customer\Enums\CustomerStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:254',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    CustomerStatus::class,
                ),
            ],

            'verified' => [
                'nullable',
                'string',
                Rule::in([
                    '1',
                    '0',
                ]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input(
            'search',
        );

        $this->merge([
            'search' => is_string($search)
                ? trim($search)
                : $search,
        ]);
    }
}
