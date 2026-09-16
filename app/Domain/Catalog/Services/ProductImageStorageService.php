<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ProductImageStorageService
{
    /**
     * @return array{
     *     path: string,
     *     original_name: string,
     *     mime_type: string|null,
     *     file_size: int|null,
     *     width: int|null,
     *     height: int|null
     * }
     */
    public function store(
        UploadedFile $image,
        int $productId,
    ): array {
        $path = $image->store(
            "catalog/products/{$productId}/images",
            'public',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'Unable to store the product image.',
            );
        }

        $dimensions = @getimagesize(
            $image->getRealPath(),
        );

        return [
            'path' => $path,

            'original_name' => $image->getClientOriginalName(),

            'mime_type' => $image->getMimeType(),

            'file_size' => $image->getSize(),

            'width' => is_array($dimensions)
                    ? $dimensions[0]
                    : null,

            'height' => is_array($dimensions)
                    ? $dimensions[1]
                    : null,
        ];
    }

    public function delete(
        ?string $path,
    ): void {
        if (
            $path === null
            || $path === ''
        ) {
            return;
        }

        Storage::disk('public')->delete(
            $path,
        );
    }
}
