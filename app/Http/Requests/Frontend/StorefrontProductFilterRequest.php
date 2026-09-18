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
        $attributes = $this->input('attributes');

        if (is_array($attributes)) {
            $attributes = collect($attributes)
                ->filter(
                    static fn (mixed $value, mixed $key): bool => is_string($key)
                        && preg_match('/^[a-z0-9-]{1,150}$/', $key) === 1
                        && is_string($value),
                )
                ->map(
                    static fn (string $value): string => trim($value),
                )
                ->filter(
                    static fn (string $value): bool => $value !== '',
                )
                ->all();
        }

        $this->merge([
            'sort' => $this->filled('sort')
                ? $this->string('sort')->trim()->toString()
                : null,

            'brand' => $this->filled('brand')
                ? $this->string('brand')->trim()->toString()
                : null,

            'category' => $this->filled('category')
                ? $this->string('category')->trim()->toString()
                : null,

            'attributes' => is_array($attributes)
                ? $attributes
                : null,
        ]);
    }
}
