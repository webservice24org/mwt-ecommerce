<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services\Storefront;

use App\Domain\Catalog\Data\Storefront\StorefrontProductListingPricingData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Services\ProductPricingResolver;
use App\Models\Product;
use App\Models\ProductVariant;
use LogicException;

final readonly class StorefrontProductListingPricingResolver
{
    public function __construct(
        private ProductPricingResolver $pricingResolver,
    ) {}

    public function resolve(
        Product $product,
    ): StorefrontProductListingPricingData {
        if ($product->type === ProductType::Simple) {
            return $this->resolveSimple($product);
        }

        return $this->resolveVariable($product);
    }

    private function resolveSimple(
        Product $product,
    ): StorefrontProductListingPricingData {
        $pricing = $this->pricingResolver->resolve(
            $product,
        );

        return new StorefrontProductListingPricingData(
            minPrice: $pricing->price,
            maxPrice: $pricing->price,
            compareAtPrice: $pricing->compareAtPrice,
            onSale: $pricing->isOnSale(),
        );
    }

    private function resolveVariable(
        Product $product,
    ): StorefrontProductListingPricingData {
        if (! $product->relationLoaded('variants')) {
            throw new LogicException(
                'Storefront listing pricing requires variants to be eager loaded.',
            );
        }

        $prices = $product->variants
            ->filter(
                static fn (
                    ProductVariant $variant,
                ): bool => $variant->is_active,
            )
            ->map(
                fn (
                    ProductVariant $variant,
                ): ?int => $this
                    ->pricingResolver
                    ->resolve(
                        product: $product,
                        variant: $variant,
                    )
                    ->price,
            )
            ->filter(
                static fn (?int $price): bool => $price !== null,
            )
            ->values();

        if ($prices->isEmpty()) {
            return new StorefrontProductListingPricingData(
                minPrice: $product->price,
                maxPrice: $product->price,
                compareAtPrice: $product->compare_at_price,
                onSale: $product->price !== null
                    && $product->compare_at_price !== null
                    && $product->compare_at_price > $product->price,
            );
        }

        return new StorefrontProductListingPricingData(
            minPrice: $prices->min(),
            maxPrice: $prices->max(),
            compareAtPrice: null,
            onSale: false,
        );
    }
}
