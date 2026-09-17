<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

final class ProductCommercialIntegrityService
{
    public function assertProductDataIsValid(
        ProductType $type,
        ?string $sku,
        ?int $price,
        ?int $compareAtPrice,
        ?int $ignoreProductId = null,
    ): void {
        if (
            $type === ProductType::Simple
            && $price === null
        ) {
            throw ValidationException::withMessages([
                'price' => 'A simple product must have a price.',
            ]);
        }

        $this->assertPricePairIsValid(
            price: $price,
            compareAtPrice: $compareAtPrice,
        );

        $this->assertSkuIsAvailable(
            sku: $sku,
            ignoreProductId: $ignoreProductId,
        );
    }

    public function assertProductTypeTransitionIsValid(
        Product $product,
        ProductType $requestedType,
    ): void {
        if (
            $product->type !== ProductType::Variable
            || $requestedType !== ProductType::Simple
        ) {
            return;
        }

        if (! $product->variants()->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'type' => 'A variable product with variants cannot be changed to a simple product. Delete its variants first.',
        ]);
    }

    public function assertVariantEffectivePricingIsValid(
        Product $product,
        ?int $variantPrice,
        ?int $variantCompareAtPrice,
    ): void {
        $effectivePrice = $variantPrice
            ?? $product->price;

        $effectiveCompareAtPrice = $variantCompareAtPrice
            ?? $product->compare_at_price;

        $this->assertPricePairIsValid(
            price: $effectivePrice,
            compareAtPrice: $effectiveCompareAtPrice,
            message: 'The effective compare-at price must be greater than or equal to the effective variant price.',
        );
    }

    public function assertProductPricingIsValidForExistingVariants(
        Product $product,
        ?int $proposedPrice,
        ?int $proposedCompareAtPrice,
    ): void {
        $variants = $product
            ->variants()
            ->get([
                'id',
                'price',
                'compare_at_price',
            ]);

        foreach ($variants as $variant) {
            $effectivePrice = $variant->price
                ?? $proposedPrice;

            $effectiveCompareAtPrice = $variant->compare_at_price
                ?? $proposedCompareAtPrice;

            if (
                $effectivePrice !== null
                && $effectiveCompareAtPrice !== null
                && $effectiveCompareAtPrice < $effectivePrice
            ) {
                throw ValidationException::withMessages([
                    'compare_at_price' => 'The product pricing would make one or more existing variants have a compare-at price lower than their effective price.',
                ]);
            }
        }
    }

    public function assertSkuIsAvailable(
        ?string $sku,
        ?int $ignoreProductId = null,
        ?int $ignoreVariantId = null,
    ): void {
        if ($sku === null || $sku === '') {
            return;
        }

        $productExists = Product::query()
            ->when(
                $ignoreProductId !== null,
                static fn ($query) => $query->where(
                    'id',
                    '!=',
                    $ignoreProductId,
                ),
            )
            ->where('sku', $sku)
            ->exists();

        if ($productExists) {
            throw ValidationException::withMessages([
                'sku' => 'The SKU has already been taken.',
            ]);
        }

        $variantExists = ProductVariant::query()
            ->when(
                $ignoreVariantId !== null,
                static fn ($query) => $query->where(
                    'id',
                    '!=',
                    $ignoreVariantId,
                ),
            )
            ->where('sku', $sku)
            ->exists();

        if ($variantExists) {
            throw ValidationException::withMessages([
                'sku' => 'The SKU has already been taken.',
            ]);
        }
    }

    private function assertPricePairIsValid(
        ?int $price,
        ?int $compareAtPrice,
        string $message = 'The compare-at price must be greater than or equal to the price.',
    ): void {
        if (
            $price === null
            || $compareAtPrice === null
            || $compareAtPrice >= $price
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'compare_at_price' => $message,
        ]);
    }
}
