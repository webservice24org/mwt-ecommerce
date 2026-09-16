<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ReorderProductImagesAction
{
    /**
     * @param  list<int>  $imageIds
     *
     * @throws Throwable
     */
    public function execute(
        Product $product,
        array $imageIds,
    ): void {
        DB::transaction(
            function () use (
                $product,
                $imageIds,
            ): void {
                $lockedProduct = Product::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $product->id,
                    );

                $requestedIds = collect(
                    $imageIds,
                )
                    ->map(
                        static fn (
                            mixed $id,
                        ): int => (int) $id,
                    )
                    ->values();

                if (
                    $requestedIds
                        ->duplicates()
                        ->isNotEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'image_ids' => 'Duplicate product images are not allowed.',
                    ]);
                }

                $existingIds = $lockedProduct
                    ->images()
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(
                        static fn (
                            mixed $id,
                        ): int => (int) $id,
                    )
                    ->values();

                $requestedSorted =
                    $requestedIds
                        ->sort()
                        ->values();

                $existingSorted =
                    $existingIds
                        ->sort()
                        ->values();

                /*
                 * Reorder requests must contain exactly
                 * the complete image set for this product.
                 */
                if (
                    $requestedSorted->all()
                    !== $existingSorted->all()
                ) {
                    throw ValidationException::withMessages([
                        'image_ids' => 'The image order does not match this product gallery.',
                    ]);
                }

                foreach (
                    $requestedIds as $position => $imageId
                ) {
                    ProductImage::query()
                        ->where(
                            'product_id',
                            $lockedProduct->id,
                        )
                        ->where(
                            'id',
                            $imageId,
                        )
                        ->update([
                            'position' => $position,
                        ]);
                }
            },
        );
    }
}
