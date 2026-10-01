<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\PageBuilderImageData;
use App\Domain\PageBuilder\Services\PageBuilderImageStorageService;
use App\Models\Page;
use Illuminate\Http\UploadedFile;

final readonly class UploadPageBuilderImageAction
{
    public function __construct(
        private PageBuilderImageStorageService $storage,
    ) {}

    public function execute(
        Page $page,
        UploadedFile $image,
    ): PageBuilderImageData {
        return $this->storage->store(
            $page,
            $image,
        );
    }
}
