<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\CreateCategoryData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('categories', 'slug'),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'position' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
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
        $slug = $this->input('slug');

        $this->merge([
            'name' => trim((string) $this->input('name')),

            'slug' => is_string($slug) && trim($slug) !== ''
                ? Str::slug($slug)
                : null,

            'description' => $this->nullableTrimmedString(
                $this->input('description'),
            ),

            'meta_title' => $this->nullableTrimmedString(
                $this->input('meta_title'),
            ),

            'meta_description' => $this->nullableTrimmedString(
                $this->input('meta_description'),
            ),
        ]);
    }

    private function nullableTrimmedString(
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

    public function toData(): CreateCategoryData
    {
        return new CreateCategoryData(
            name: $this->string('name')->toString(),

            slug: $this->filled('slug')
                ? $this->string('slug')->toString()
                : null,

            parentId: $this->filled('parent_id')
                ? $this->integer('parent_id')
                : null,

            description: $this->filled('description')
                ? $this->string('description')->toString()
                : null,

            position: $this->integer('position'),

            isActive: $this->boolean('is_active'),

            metaTitle: $this->filled('meta_title')
                ? $this->string('meta_title')->toString()
                : null,

            metaDescription: $this->filled('meta_description')
                ? $this->string('meta_description')->toString()
                : null,
        );
    }

    public function image(): ?UploadedFile
    {
        $image = $this->file('image');

        return $image instanceof UploadedFile
            ? $image
            : null;
    }
}
