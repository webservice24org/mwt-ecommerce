<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\UpdateBrandData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class UpdateBrandRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:180',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_logo' => [
                'sometimes',
                'boolean',
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

            'meta_title' => [
                'nullable',
                'string',
                'max:180',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:320',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => $this->normalizeNullableString(
                $this->input('slug'),
            ),
            'description' => $this->normalizeNullableString(
                $this->input('description'),
            ),
            'meta_title' => $this->normalizeNullableString(
                $this->input('meta_title'),
            ),
            'meta_description' => $this->normalizeNullableString(
                $this->input('meta_description'),
            ),
        ]);
    }

    public function toData(): UpdateBrandData
    {
        return new UpdateBrandData(
            name: $this->string('name')->toString(),
            slug: $this->input('slug'),
            description: $this->input('description'),
            position: $this->integer('position'),
            isActive: $this->boolean('is_active'),
            metaTitle: $this->input('meta_title'),
            metaDescription: $this->input('meta_description'),
        );
    }

    public function logo(): ?UploadedFile
    {
        $logo = $this->file('logo');

        return $logo instanceof UploadedFile
            ? $logo
            : null;
    }

    public function shouldRemoveLogo(): bool
    {
        return $this->boolean('remove_logo');
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
