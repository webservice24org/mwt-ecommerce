<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Services\ProductImageStorageService;
use App\Domain\Catalog\Services\ProductMediaIntegrityService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteProductImageAction
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
        ProductImage $image,
    ): void {
        $path = DB::transaction(
            function () use (
                $product,
                $image,
            ): string {
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

                $path = $lockedImage->path;
                $wasPrimary =
                    $lockedImage->is_primary;

                $lockedImage->delete();

                $this->integrity
                    ->normalizePositions(
                        $lockedProduct,
                    );

                if ($wasPrimary) {
                    $this->integrity
                        ->promotePrimaryImage(
                            $lockedProduct,
                        );
                }

                return $path;
            },
        );

        /*
         * DB succeeds first.
         *
         * We never want the DB pointing at a file
         * that was already physically deleted.
         */
        $this->storage->delete(
            $path,
        );
    }
}
