<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Orders;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
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
