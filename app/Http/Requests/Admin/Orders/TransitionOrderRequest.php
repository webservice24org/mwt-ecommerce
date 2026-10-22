<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Orders;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

final class TransitionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input(
            'note',
        );

        $this->merge([
            'note' => is_string($note)
                ? trim($note)
                : $note,
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function note(): ?string
    {
        $note = $this->validated(
            'note',
        );

        if (
            ! is_string($note) ||
            $note === ''
        ) {
            return null;
        }

        return $note;
    }
}
