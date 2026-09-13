<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\UpdateAttributeValueData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeRequiredString(
                $this->input('name'),
            ),

            'slug' => $this->normalizeNullableString(
                $this->input('slug'),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:150',
            ],

            'position' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function toData(): UpdateAttributeValueData
    {
        return new UpdateAttributeValueData(
            name: (string) $this->input(
                'name',
            ),

            slug: $this->nullableString(
                'slug',
            ),

            position: (int) $this->input(
                'position',
                0,
            ),

            isActive: $this->boolean(
                'is_active',
            ),
        );
    }

    private function normalizeRequiredString(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value);
    }

    private function normalizeNullableString(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function nullableString(
        string $key,
    ): ?string {
        $value = $this->input($key);

        return is_string($value)
            ? $value
            : null;
    }
}
