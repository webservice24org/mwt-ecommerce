<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateProductImageData;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateProductImageAction
{
    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
        ProductImage $image,
        UpdateProductImageData $data,
    ): ProductImage {
        return DB::transaction(
            function () use (
                $product,
                $image,
                $data,
            ): ProductImage {
                Product::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $product->id,
                    );

                $lockedImage = ProductImage::query()
                    ->where(
                        'product_id',
                        $product->id,
                    )
                    ->lockForUpdate()
                    ->findOrFail(
                        $image->id,
                    );

                $lockedImage->update([
                    'alt_text' => $data->altText,
                ]);

                return $lockedImage->refresh();
            },
        );
    }
}
