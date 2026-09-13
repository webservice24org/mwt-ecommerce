<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateProductVariantData;
use App\Domain\Catalog\Services\ProductVariantIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProductVariantAction
{
    public function __construct(
        private ProductVariantIntegrityService $integrity,
    ) {}

    public function execute(
        ProductVariant $variant,
        UpdateProductVariantData $data,
    ): ProductVariant {
        return DB::transaction(
            function () use ($variant, $data): ProductVariant {
                $product = Product::query()
                    ->whereKey($variant->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedVariant = ProductVariant::query()
                    ->whereKey($variant->id)
                    ->where(
                        'product_id',
                        $product->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedVariant->setRelation(
                    'product',
                    $product,
                );

                $this->integrity->validateAttributeValues(
                    $data->attributeValueIds,
                );

                $this->integrity->ensureCombinationIsUnique(
                    product: $product,
                    attributeValueIds: $data->attributeValueIds,
                    ignoreVariant: $lockedVariant,
                );

                $this->integrity
                    ->assertDefaultCanBeUnset(
                        $lockedVariant,
                        $data->isDefault,
                    );

                if ($data->isDefault) {
                    $this->integrity
                        ->clearOtherDefaults(
                            product: $product,
                            except: $lockedVariant,
                        );
                }

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

                return $lockedVariant
                    ->refresh()
                    ->load(
                        'attributeValues.attribute',
                    );
            },
        );
    }
}
