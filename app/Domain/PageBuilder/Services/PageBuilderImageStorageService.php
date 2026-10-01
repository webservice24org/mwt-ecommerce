<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Services;

use App\Domain\PageBuilder\Data\PageBuilderImageData;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PageBuilderImageStorageService
{
    private const DISK = 'public';

    public function store(
        Page $page,
        UploadedFile $file,
    ): PageBuilderImageData {
        $directory = sprintf(
            'page-builder/pages/%d/images',
            $page->getKey(),
        );

        $path = $file->store(
            $directory,
            self::DISK,
        );

        if ($path === false) {
            throw new RuntimeException(
                'Unable to store the Page Builder image.',
            );
        }

        $dimensions = @getimagesize(
            $file->getRealPath(),
        );

        $width = is_array($dimensions)
            ? (int) $dimensions[0]
            : null;

        $height = is_array($dimensions)
            ? (int) $dimensions[1]
            : null;

        $mimeType = $file->getMimeType();

        if ($mimeType === null) {
            $mimeType =
                'application/octet-stream';
        }

        return new PageBuilderImageData(
            path: $path,
            url: '/storage/'.$path,
            originalName: $file->getClientOriginalName(),
            mimeType: $mimeType,
            fileSize: (int) $file->getSize(),
            width: $width,
            height: $height,
        );
    }

    public function delete(
        string $path,
    ): void {
        Storage::disk(self::DISK)
            ->delete($path);
    }
}
