<?php

declare(strict_types=1);

namespace App\Http\Requests\Frontend;

use App\Domain\Catalog\Data\Storefront\StorefrontProductFiltersData;
use App\Domain\Catalog\Enums\StorefrontProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorefrontProductFilterRequest extends FormRequest
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
            'sort' => [
                'nullable',
                'string',
                Rule::enum(StorefrontProductSort::class),
            ],

            'brand' => [
                'nullable',
                'string',
                'max:180',
            ],

            'category' => [
                'nullable',
                'string',
                'max:180',
            ],

            'min_price' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'max_price' => [
                'nullable',
                'integer',
                'min:0',
                'gte:min_price',
            ],

            'attributes' => [
                'nullable',
                'array',
                'max:20',
            ],

            'attributes.*' => [
                'string',
                'max:180',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function filters(): StorefrontProductFiltersData
    {
        /** @var array{
         *     sort?: string|null,
         *     brand?: string|null,
         *     category?: string|null,
         *     min_price?: int|string|null,
         *     max_price?: int|string|null,
         *     attributes?: array<string, string>|null
         * } $validated
         */
        $validated = $this->validated();

        return StorefrontProductFiltersData::fromValidated(
            $validated,
        );
    }

    protected function prepareForValidation(): void
    {
        $sort = $this->input('sort');
        $brand = $this->input('brand');
        $category = $this->input('category');
        $attributes = $this->input('attributes');

        $normalizedAttributes = [];

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                if (
                    preg_match(
                        '/^[a-z0-9-]{1,150}$/',
                        $key,
                    ) !== 1
                ) {
                    continue;
                }

                if (! is_string($value)) {
                    continue;
                }

                $value = trim($value);

                if ($value === '') {
                    continue;
                }

                $normalizedAttributes[$key] = $value;
            }
        }

        $this->merge([
            'sort' => is_string($sort)
                ? trim($sort)
                : $sort,

            'brand' => is_string($brand)
                ? trim($brand)
                : $brand,

            'category' => is_string($category)
                ? trim($category)
                : $category,

            'attributes' => is_array($attributes)
                ? $normalizedAttributes
                : $attributes,
        ]);
    }
}
