<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\CreateProductVariantData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->normalizeRequiredString(
                $this->input('sku'),
            ),
            'name' => $this->normalizeNullableString(
                $this->input('name'),
            ),
            'barcode' => $this->normalizeNullableString(
                $this->input('barcode'),
            ),
            'weight' => $this->normalizeNullableString(
                $this->input('weight'),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku'),
            ],

            'name' => [
                'nullable',
                'string',
                'max:180',
            ],

            'price' => [
                'required',
                'integer',
                'min:0',
            ],

            'compare_at_price' => [
                'nullable',
                'integer',
                'min:0',
                'gte:price',
            ],

            'cost_price' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'product_variants',
                    'barcode',
                ),
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

            'is_default' => [
                'required',
                'boolean',
            ],

            'weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'attribute_value_ids' => [
                'array',
            ],

            'attribute_value_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(
                    'attribute_values',
                    'id',
                ),
            ],
        ];
    }

    public function toData(): CreateProductVariantData
    {
        /** @var list<int> $attributeValueIds */
        $attributeValueIds = array_values(
            array_map(
                'intval',
                $this->validated(
                    'attribute_value_ids',
                    [],
                ),
            ),
        );

        return new CreateProductVariantData(
            sku: (string) $this->validated('sku'),
            name: $this->nullableString('name'),
            price: (int) $this->validated('price'),
            compareAtPrice: $this->nullableInt(
                'compare_at_price',
            ),
            costPrice: $this->nullableInt(
                'cost_price',
            ),
            barcode: $this->nullableString('barcode'),
            position: (int) $this->validated('position'),
            isActive: (bool) $this->validated(
                'is_active',
            ),
            isDefault: (bool) $this->validated(
                'is_default',
            ),
            weight: $this->nullableString('weight'),
            attributeValueIds: $attributeValueIds,
        );
    }

    private function normalizeRequiredString(
        mixed $value,
    ): mixed {
        return is_string($value)
            ? trim($value)
            : $value;
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
        $value = $this->validated($key);

        return is_string($value)
            && $value !== ''
                ? $value
                : null;
    }

    private function nullableInt(
        string $key,
    ): ?int {
        $value = $this->validated($key);

        return $value === null
            ? null
            : (int) $value;
    }
}
