<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ProductVideoStorageService
{
    /**
     * @return array{
     *     path: string,
     *     original_name: string,
     *     mime_type: string|null,
     *     file_size: int|null
     * }
     */
    public function store(
        UploadedFile $video,
        int $productId,
    ): array {
        $path = $video->store(
            "catalog/products/{$productId}/videos",
            'public',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'Unable to store the product video.',
            );
        }

        return [
            'path' => $path,

            'original_name' => $video->getClientOriginalName(),

            'mime_type' => $video->getMimeType(),

            'file_size' => $video->getSize(),
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
