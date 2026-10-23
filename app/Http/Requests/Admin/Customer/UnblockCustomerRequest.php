<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;

final class UnblockCustomerRequest extends FormRequest
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
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function reason(): ?string
    {
        $reason = $this->validated(
            'reason',
        );

        return is_string($reason)
            ? $reason
            : null;
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input(
            'reason',
        );

        if (! is_string($reason)) {
            return;
        }

        $reason = trim(
            $reason,
        );

        $this->merge([
            'reason' => $reason === ''
                ? null
                : $reason,
        ]);
    }
}
