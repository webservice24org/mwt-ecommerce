<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Enums\ProductType;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

final class ProductVariantIntegrityService
{
    public function assertCommercialDataIsValid(
        ?int $price,
        ?int $compareAtPrice,
    ): void {
        if (
            $price !== null
            && $compareAtPrice !== null
            && $compareAtPrice < $price
        ) {
            throw ValidationException::withMessages([
                'compare_at_price' => 'The compare-at price must be greater than or equal to the variant price.',
            ]);
        }
    }

    public function assertProductSupportsVariants(
        Product $product,
    ): void {
        if ($product->type === ProductType::Variable) {
            return;
        }

        throw ValidationException::withMessages([
            'type' => 'Variants can only be managed for variable products.',
        ]);
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    public function validateAttributeValues(
        array $attributeValueIds,
    ): void {
        if ($attributeValueIds === []) {
            return;
        }

        $ids = collect($attributeValueIds)
            ->unique()
            ->values();

        if ($ids->count() !== count($attributeValueIds)) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'Duplicate attribute values are not allowed.',
            ]);
        }

        $values = AttributeValue::query()
            ->whereIn(
                'id',
                $ids->all(),
            )
            ->get([
                'id',
                'attribute_id',
            ]);

        if ($values->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'One or more selected attribute values are invalid.',
            ]);
        }

        if (
            $values
                ->pluck('attribute_id')
                ->duplicates()
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'A variant may contain only one value from each attribute.',
            ]);
        }
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    public function ensureCombinationIsUnique(
        Product $product,
        array $attributeValueIds,
        ?ProductVariant $ignoreVariant = null,
    ): void {
        /*
         * Variants without attribute values are allowed.
         *
         * Example:
         *
         * SKU: PRODUCT-001
         * SKU: PRODUCT-002
         *
         * Both may legitimately have an empty attribute
         * combination because their SKU distinguishes them.
         */
        if ($attributeValueIds === []) {
            return;
        }

        $expectedIds = collect(
            $attributeValueIds,
        )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $variants = $product
            ->variants()
            ->with(
                'attributeValues:id',
            )
            ->when(
                $ignoreVariant !== null,
                static fn ($query) => $query->where(
                    'id',
                    '!=',
                    $ignoreVariant->id,
                ),
            )
            ->get();

        foreach ($variants as $variant) {
            $existingIds = $variant
                ->attributeValues
                ->pluck('id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            /*
             * A variant without attributes must not
             * conflict with an attributed variant.
             */
            if ($existingIds === []) {
                continue;
            }

            if ($existingIds === $expectedIds) {
                throw ValidationException::withMessages([
                    'attribute_value_ids' => 'This attribute combination already exists for this product.',
                ]);
            }
        }
    }

    public function clearOtherDefaults(
        Product $product,
        ?ProductVariant $except = null,
    ): void {
        $product
            ->variants()
            ->when(
                $except !== null,
                static fn ($query) => $query->where(
                    'id',
                    '!=',
                    $except->id,
                ),
            )
            ->where(
                'is_default',
                true,
            )
            ->update([
                'is_default' => false,
            ]);
    }

    public function assertDefaultCanBeUnset(
        ProductVariant $variant,
        bool $requestedDefault,
    ): void {
        /*
         * Nothing to validate when:
         *
         * - this variant is not currently default
         * - or the request keeps it as default
         */
        if (
            ! $variant->is_default
            || $requestedDefault
        ) {
            return;
        }

        $hasAnotherVariant = $variant
            ->product
            ->variants()
            ->where(
                'id',
                '!=',
                $variant->id,
            )
            ->exists();

        /*
         * The last remaining variant may remain handled
         * by the update action. There is no alternative
         * variant that the administrator can select.
         */
        if (! $hasAnotherVariant) {
            return;
        }

        throw ValidationException::withMessages([
            'is_default' => 'Choose another variant as default before removing the current default.',
        ]);
    }

    public function assertInactiveVariantCannotBecomeDefault(
        bool $isActive,
        bool $isDefault,
    ): void {
        if (
            $isActive
            || ! $isDefault
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'is_default' => 'An inactive variant cannot be selected as the default variant.',
        ]);
    }

    public function promoteActiveReplacementDefault(
        Product $product,
        ProductVariant $currentDefault,
    ): bool {
        $replacement = $product
            ->variants()
            ->where(
                'id',
                '!=',
                $currentDefault->id,
            )
            ->where(
                'is_active',
                true,
            )
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($replacement === null) {
            return false;
        }

        $this->clearOtherDefaults(
            product: $product,
            except: $replacement,
        );

        $replacement->update([
            'is_default' => true,
        ]);

        return true;
    }

    public function promoteReplacementDefault(
        Product $product,
    ): void {
        $replacement = $product
            ->variants()
            ->orderByDesc('is_active')
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($replacement === null) {
            return;
        }

        $this->clearOtherDefaults(
            product: $product,
            except: $replacement,
        );

        $replacement->update([
            'is_default' => true,
        ]);
    }
}
