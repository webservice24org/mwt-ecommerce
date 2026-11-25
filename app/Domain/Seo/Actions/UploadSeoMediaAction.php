<?php

declare(strict_types=1);

namespace App\Domain\Seo\Actions;

use App\Domain\Seo\Data\SeoMediaData;
use App\Support\Media\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class UploadSeoMediaAction
{
    public function __construct(
        private ImageStorageService $imageStorage,
    ) {}

    public function execute(
        UploadedFile $image,
    ): SeoMediaData {
        $path =
            $this
                ->imageStorage
                ->store(
                    image: $image,

                    directory: 'seo/social',
                );

        $relativeUrl =
            Storage::url(
                $path,
            );

        $absoluteUrl =
            url(
                $relativeUrl,
            );

        return new SeoMediaData(
            path: $path,

            url: $absoluteUrl,
        );
    }
}
