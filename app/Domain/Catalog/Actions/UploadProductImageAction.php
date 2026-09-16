<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateProductImageData;
use App\Domain\Catalog\Services\ProductImageStorageService;
use App\Domain\Catalog\Services\ProductMediaIntegrityService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UploadProductImageAction
{
    public function __construct(
        private ProductImageStorageService $storage,
        private ProductMediaIntegrityService $integrity,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
        CreateProductImageData $data,
    ): ProductImage {
        $stored = $this->storage->store(
            image: $data->image,
            productId: $product->id,
        );

        try {
            return DB::transaction(
                function () use (
                    $product,
                    $data,
                    $stored,
                ): ProductImage {
                    $lockedProduct = Product::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $product->id,
                        );

                    $hasImages = $lockedProduct
                        ->images()
                        ->exists();

                    /*
                     * First image always becomes featured.
                     */
                    $makePrimary =
                        ! $hasImages
                        || $data->isPrimary;

                    if ($makePrimary) {
                        $this->integrity
                            ->clearOtherPrimaryImages(
                                $lockedProduct,
                            );
                    }

                    $image = $lockedProduct
                        ->images()
                        ->create([
                            'path' => $stored['path'],

                            'original_name' => $stored[
                                    'original_name'
                                ],

                            'mime_type' => $stored[
                                    'mime_type'
                                ],

                            'file_size' => $stored[
                                    'file_size'
                                ],

                            'width' => $stored['width'],

                            'height' => $stored['height'],

                            'alt_text' => $data->altText,

                            'position' => $this
                                ->integrity
                                ->nextImagePosition(
                                    $lockedProduct,
                                ),

                            'is_primary' => $makePrimary,
                        ]);

                    return $image;
                },
            );
        } catch (Throwable $exception) {
            /*
             * Database failed after file upload.
             * Do not leave an orphaned file.
             */
            $this->storage->delete(
                $stored['path'],
            );

            throw $exception;
        }
    }
}
