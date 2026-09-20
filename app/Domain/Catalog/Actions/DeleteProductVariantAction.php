<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Services\ProductVariantIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final readonly class DeleteProductVariantAction
{
    public function __construct(
        private ProductVariantIntegrityService $integrity,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        ProductVariant $variant,
    ): void {
        DB::transaction(
            function () use ($variant): void {
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

                $wasDefault =
                    $lockedVariant->is_default;

                $lockedVariant->delete();

                if ($wasDefault) {
                    $this->integrity
                        ->promoteReplacementDefault(
                            $product,
                        );
                }
            },
        );

        $this->storefrontCache->invalidate();
    }
}
