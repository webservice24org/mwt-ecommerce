<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class SearchPageBuilderProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'required',
                'string',
                'max:120',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge([
                'search' => trim(
                    (string) $this->input('search'),
                ),
            ]);
        }
    }

    public function search(): string
    {
        return (string) $this->validated('search');
    }
}
