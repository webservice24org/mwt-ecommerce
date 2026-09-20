<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\SaveProductVideoData;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Domain\Catalog\Services\ProductVideoStorageService;
use App\Domain\Catalog\Services\ProductVideoUrlService;
use App\Models\Product;
use App\Models\ProductVideo;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class SaveProductVideoAction
{
    public function __construct(
        private ProductVideoStorageService $storage,
        private ProductVideoUrlService $urls,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        Product $product,
        SaveProductVideoData $data,
    ): ProductVideo {
        $normalizedUrl = $this->urls->validate(
            type: $data->type,
            url: $data->url,
        );

        if (
            $data->type === ProductVideoType::Upload
            && $data->video === null
        ) {
            throw ValidationException::withMessages([
                'video' => 'Select a product video to upload.',
            ]);
        }

        $stored = null;

        if ($data->type === ProductVideoType::Upload) {
            /*
             * PHPStan knows $data->video cannot be null here
             * because the null case above already throws.
             */
            $stored = $this->storage->store(
                video: $data->video,
                productId: $product->id,
            );
        }

        $oldUploadedPath = null;

        try {
            $result = DB::transaction(
                function () use (
                    $product,
                    $data,
                    $normalizedUrl,
                    $stored,
                    &$oldUploadedPath,
                ): ProductVideo {
                    $lockedProduct = Product::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $product->id,
                        );

                    $existing = $lockedProduct
                        ->video()
                        ->lockForUpdate()
                        ->first();

                    if (
                        $existing !== null
                        && $existing->type
                            === ProductVideoType::Upload
                    ) {
                        $oldUploadedPath =
                            $existing->path;
                    }

                    $values = [
                        'type' => $data->type,

                        'title' => $data->title,

                        'url' => $normalizedUrl,

                        'path' => $stored['path']
                            ?? null,

                        'original_name' => $stored['original_name']
                            ?? null,

                        'mime_type' => $stored['mime_type']
                            ?? null,

                        'file_size' => $stored['file_size']
                            ?? null,
                    ];

                    if ($existing === null) {
                        return $lockedProduct
                            ->video()
                            ->create(
                                $values,
                            );
                    }

                    $existing->update(
                        $values,
                    );

                    return $existing->refresh();
                },
            );
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->storage->delete(
                    $stored['path'],
                );
            }

            throw $exception;
        }

        $this->storefrontCache->invalidate();

        if (
            $oldUploadedPath !== null
            && (
                $stored === null
                || $oldUploadedPath
                    !== $stored['path']
            )
        ) {
            $this->storage->delete(
                $oldUploadedPath,
            );
        }

        return $result;
    }
}
