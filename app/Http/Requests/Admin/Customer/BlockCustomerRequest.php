<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;

final class BlockCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ];
    }

    public function reason(): string
    {
        return (string) $this->validated(
            'reason',
        );
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input(
            'reason',
        );

        $this->merge([
            'reason' => is_string($reason)
                ? trim($reason)
                : $reason,
        ]);
    }
}
