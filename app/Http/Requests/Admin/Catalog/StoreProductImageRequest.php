<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\CreateProductImageData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class StoreProductImageRequest extends FormRequest
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
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('alt_text')) {
            $this->merge([
                'alt_text' => $this->normalizeNullableString(
                    $this->input('alt_text'),
                ),
            ]);
        }
    }

    public function toData(): CreateProductImageData
    {
        $image = $this->file('image');

        if (! $image instanceof UploadedFile) {
            abort(422);
        }

        return new CreateProductImageData(
            image: $image,
            altText: $this->validated('alt_text'),
            isPrimary: $this->boolean('is_primary'),
        );
    }

    private function normalizeNullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
