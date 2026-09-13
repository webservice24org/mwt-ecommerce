<?php

declare(strict_types=1);

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ImageStorageService
{
    public function store(
        UploadedFile $image,
        string $directory,
    ): string {
        $path = $image->store(
            $directory,
            'public',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'Unable to store the uploaded image.',
            );
        }

        return $path;
    }

    public function delete(
        ?string $path,
    ): void {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
