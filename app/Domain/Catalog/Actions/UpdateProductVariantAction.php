<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateProductVariantData;
use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Domain\Catalog\Services\ProductVariantIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductVariantAction
{
    public function __construct(
        private ProductVariantIntegrityService $integrity,
        private ProductCommercialIntegrityService $commercialIntegrity,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        ProductVariant $variant,
        UpdateProductVariantData $data,
    ): ProductVariant {
        $updatedVariant = DB::transaction(
            function () use ($variant, $data): ProductVariant {
                $product = Product::query()
                    ->whereKey($variant->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->integrity->assertProductSupportsVariants(
                    $product,
                );

                $lockedVariant = ProductVariant::query()
                    ->whereKey($variant->id)
                    ->where(
                        'product_id',
                        $product->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->integrity->assertCommercialDataIsValid(
                    price: $data->price,
                    compareAtPrice: $data->compareAtPrice,
                );

                $this->commercialIntegrity
                    ->assertVariantEffectivePricingIsValid(
                        product: $product,
                        variantPrice: $data->price,
                        variantCompareAtPrice: $data->compareAtPrice,
                    );

                $lockedVariant->setRelation(
                    'product',
                    $product,
                );

                $this->integrity->assertCommercialDataIsValid(
                    price: $data->price,
                    compareAtPrice: $data->compareAtPrice,
                );

                $this->integrity->validateAttributeValues(
                    $data->attributeValueIds,
                );

                $this->integrity->ensureCombinationIsUnique(
                    product: $product,
                    attributeValueIds: $data->attributeValueIds,
                    ignoreVariant: $lockedVariant,
                );

                if (
                    $data->isDefault
                    && ! $data->isActive
                    && ! $lockedVariant->is_default
                ) {
                    $this->integrity
                        ->assertInactiveVariantCannotBecomeDefault(
                            isActive: $data->isActive,
                            isDefault: $data->isDefault,
                        );
                }

                if ($data->isActive) {
                    $this->integrity
                        ->assertDefaultCanBeUnset(
                            $lockedVariant,
                            $data->isDefault,
                        );
                }

                if ($data->isDefault) {
                    $this->integrity
                        ->clearOtherDefaults(
                            product: $product,
                            except: $lockedVariant,
                        );
                }

                $wasDefault = $lockedVariant->is_default;

                $lockedVariant->update([
                    'sku' => $data->sku,
                    'name' => $data->name,
                    'price' => $data->price,
                    'compare_at_price' => $data->compareAtPrice,
                    'cost_price' => $data->costPrice,
                    'barcode' => $data->barcode,
                    'position' => $data->position,
                    'is_active' => $data->isActive,
                    'is_default' => $data->isDefault,
                    'weight' => $data->weight,
                ]);

                $lockedVariant
                    ->attributeValues()
                    ->sync(
                        $data->attributeValueIds,
                    );

                if (
                    $wasDefault
                    && ! $data->isActive
                ) {
                    $promoted = $this->integrity
                        ->promoteActiveReplacementDefault(
                            product: $product,
                            currentDefault: $lockedVariant,
                        );

                    if (! $promoted) {
                        $lockedVariant->update([
                            'is_default' => true,
                        ]);
                    }
                }

                return $lockedVariant
                    ->refresh()
                    ->load(
                        'attributeValues.attribute',
                    );
            },
        );

        $this->storefrontCache->invalidate();

        return $updatedVariant;
    }
}
