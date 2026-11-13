<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reviews;

use Illuminate\Foundation\Http\FormRequest;

final class ModerateProductReviewRequest extends FormRequest
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
            'note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $note =
            $this->input(
                'note',
            );

        if (
            is_string($note)
        ) {
            $this->merge([
                'note' => trim($note) === ''
                    ? null
                    : trim($note),
            ]);
        }
    }
}
