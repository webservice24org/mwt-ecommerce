<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\CreateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeString(
                $this->input('name'),
            ),
            'slug' => $this->normalizeNullableString(
                $this->input('slug'),
            ),
            'short_description' => $this->normalizeNullableString(
                $this->input('short_description'),
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
            'published_at' => $this->normalizeNullableString(
                $this->input('published_at'),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            'name' => [
                'required',
                'string',
                'max:180',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:200',
            ],

            'short_description' => [
                'nullable',
                'string',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::enum(ProductStatus::class),
            ],

            'is_featured' => [
                'required',
                'boolean',
            ],

            'position' => [
                'required',
                'integer',
                'min:0',
            ],

            'published_at' => [
                'nullable',
                'date',
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

            'category_ids' => [
                'array',
            ],

            'category_ids.*' => [
                'integer',
                'distinct',
                'exists:categories,id',
            ],
        ];
    }

    public function toData(): CreateProductData
    {
        return new CreateProductData(
            brandId: $this->nullableInteger('brand_id'),
            name: (string) $this->input('name'),
            slug: $this->nullableString('slug'),
            shortDescription: $this->nullableString(
                'short_description',
            ),
            description: $this->nullableString(
                'description',
            ),
            status: ProductStatus::from(
                (string) $this->input('status'),
            ),
            isFeatured: $this->boolean(
                'is_featured',
            ),
            position: (int) $this->input(
                'position',
                0,
            ),
            publishedAt: $this->nullableDate(
                'published_at',
            ),
            metaTitle: $this->nullableString(
                'meta_title',
            ),
            metaDescription: $this->nullableString(
                'meta_description',
            ),
            categoryIds: $this->categoryIds(),
        );
    }

    private function normalizeString(
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

    private function nullableInteger(
        string $key,
    ): ?int {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableDate(
        string $key,
    ): ?CarbonImmutable {
        $value = $this->nullableString($key);

        return $value === null
            ? null
            : CarbonImmutable::parse($value);
    }

    /**
     * @return list<int>
     */
    private function categoryIds(): array
    {
        $values = $this->input(
            'category_ids',
            [],
        );

        if (! is_array($values)) {
            return [];
        }

        return array_values(
            array_map(
                static fn (mixed $value): int => (int) $value,
                $values,
            ),
        );
    }
}
