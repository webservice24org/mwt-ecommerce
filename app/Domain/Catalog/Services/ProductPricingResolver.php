<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Data\ResolvedProductPricing;
use App\Models\Product;
use App\Models\ProductVariant;
use InvalidArgumentException;

final class ProductPricingResolver
{
    public function resolve(
        Product $product,
        ?ProductVariant $variant = null,
    ): ResolvedProductPricing {
        if ($variant === null) {
            return new ResolvedProductPricing(
                price: $product->price,
                compareAtPrice: $product->compare_at_price,
                costPrice: $product->cost_price,
                priceInherited: false,
                compareAtPriceInherited: false,
                costPriceInherited: false,
            );
        }

        $this->assertVariantBelongsToProduct(
            product: $product,
            variant: $variant,
        );

        return new ResolvedProductPricing(
            price: $variant->price
                ?? $product->price,

            compareAtPrice: $variant->compare_at_price
                ?? $product->compare_at_price,

            costPrice: $variant->cost_price
                ?? $product->cost_price,

            priceInherited: $variant->price === null,

            compareAtPriceInherited: $variant->compare_at_price === null,

            costPriceInherited: $variant->cost_price === null,
        );
    }

    private function assertVariantBelongsToProduct(
        Product $product,
        ProductVariant $variant,
    ): void {
        if ($variant->product_id === $product->id) {
            return;
        }

        throw new InvalidArgumentException(
            'The variant does not belong to the supplied product.',
        );
    }
}
