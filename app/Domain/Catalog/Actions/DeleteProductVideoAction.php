<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductVideoType;
use App\Domain\Catalog\Services\ProductVideoStorageService;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteProductVideoAction
{
    public function __construct(
        private ProductVideoStorageService $storage,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
    ): void {
        $uploadedPath = DB::transaction(
            function () use (
                $product,
            ): ?string {
                $lockedProduct = Product::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $product->id,
                    );

                $video = $lockedProduct
                    ->video()
                    ->lockForUpdate()
                    ->first();

                if ($video === null) {
                    return null;
                }

                $path =
                    $video->type
                        === ProductVideoType::Upload
                    ? $video->path
                    : null;

                $video->delete();

                return $path;
            },
        );

        $this->storage->delete(
            $uploadedPath,
        );
    }
}
