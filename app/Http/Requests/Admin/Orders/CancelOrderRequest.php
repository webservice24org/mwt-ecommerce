<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Orders;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

final class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
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
                'max:1000',
            ],
        ];
    }

    public function reason(): string
    {
        return (string) $this->validated(
            'reason',
        );
    }
}
