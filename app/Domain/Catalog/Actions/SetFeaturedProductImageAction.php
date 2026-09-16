<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Services\ProductMediaIntegrityService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class SetFeaturedProductImageAction
{
    public function __construct(
        private ProductMediaIntegrityService $integrity,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
        ProductImage $image,
    ): ProductImage {
        return DB::transaction(
            function () use (
                $product,
                $image,
            ): ProductImage {
                $lockedProduct = Product::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $product->id,
                    );

                $lockedImage = ProductImage::query()
                    ->where(
                        'product_id',
                        $lockedProduct->id,
                    )
                    ->lockForUpdate()
                    ->findOrFail(
                        $image->id,
                    );

                $this->integrity
                    ->clearOtherPrimaryImages(
                        product: $lockedProduct,
                        except: $lockedImage,
                    );

                if (! $lockedImage->is_primary) {
                    $lockedImage->update([
                        'is_primary' => true,
                    ]);
                }

                return $lockedImage->refresh();
            },
        );
    }
}
