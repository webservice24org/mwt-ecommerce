<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateProductVariantData;
use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Domain\Catalog\Services\ProductVariantIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductVariantAction
{
    public function __construct(
        private ProductVariantIntegrityService $integrity,
        private ProductCommercialIntegrityService $commercialIntegrity,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        Product $product,
        CreateProductVariantData $data,
    ): ProductVariant {
        $variant = DB::transaction(
            function () use ($product, $data): ProductVariant {
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->integrity->assertProductSupportsVariants(
                    $lockedProduct,
                );

                $this->commercialIntegrity->assertSkuIsAvailable(
                    sku: $data->sku,
                );

                $this->integrity->assertCommercialDataIsValid(
                    price: $data->price,
                    compareAtPrice: $data->compareAtPrice,
                );

                $this->commercialIntegrity
                    ->assertVariantEffectivePricingIsValid(
                        product: $lockedProduct,
                        variantPrice: $data->price,
                        variantCompareAtPrice: $data->compareAtPrice,
                    );

                $this->integrity->validateAttributeValues(
                    $data->attributeValueIds,
                );

                $this->integrity->ensureCombinationIsUnique(
                    product: $lockedProduct,
                    attributeValueIds: $data->attributeValueIds,
                );

                $isFirstVariant = ! $lockedProduct
                    ->variants()
                    ->exists();

                $isDefault = $isFirstVariant
                    || $data->isDefault;

                if (! $isFirstVariant) {
                    $this->integrity
                        ->assertInactiveVariantCannotBecomeDefault(
                            isActive: $data->isActive,
                            isDefault: $data->isDefault,
                        );
                }

                if ($isDefault) {
                    $this->integrity->clearOtherDefaults(
                        $lockedProduct,
                    );
                }

                $variant = $lockedProduct
                    ->variants()
                    ->create([
                        'sku' => $data->sku,
                        'name' => $data->name,
                        'price' => $data->price,
                        'compare_at_price' => $data->compareAtPrice,
                        'cost_price' => $data->costPrice,
                        'barcode' => $data->barcode,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'is_default' => $isDefault,
                        'weight' => $data->weight,
                    ]);

                $variant
                    ->attributeValues()
                    ->sync(
                        $data->attributeValueIds,
                    );

                return $variant->load(
                    'attributeValues.attribute',
                );
            },
        );

        $this->storefrontCache->invalidate();

        return $variant;
    }
}
