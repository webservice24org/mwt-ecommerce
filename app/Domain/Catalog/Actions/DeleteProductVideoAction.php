<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductVideoType;
use App\Domain\Catalog\Services\ProductVideoStorageService;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteProductVideoAction
{
    public function __construct(
        private ProductVideoStorageService $storage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
    ): void {
        $result = DB::transaction(
            function () use (
                $product,
            ): array {
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
                    return [
                        'deleted' => false,
                        'uploaded_path' => null,
                    ];
                }

                $path =
                    $video->type
                        === ProductVideoType::Upload
                    ? $video->path
                    : null;

                $video->delete();

                return [
                    'deleted' => true,
                    'uploaded_path' => $path,
                ];
            },
        );

        if (! $result['deleted']) {
            return;
        }

        $this->storefrontCache->invalidate();

        $uploadedPath = $result['uploaded_path'];

        $this->storage->delete(
            is_string($uploadedPath)
                ? $uploadedPath
                : null,
        );
    }
}
