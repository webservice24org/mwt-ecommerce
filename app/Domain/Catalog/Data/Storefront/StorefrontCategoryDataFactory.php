<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Models\Category;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final readonly class StorefrontCategoryDataFactory
{
    public function detail(
        Category $category,
    ): StorefrontCategoryDetailData {
        return StorefrontCategoryDetailData::fromModel(
            $category,
            $this->imageUrl($category->image_path),
        );
    }

    private function imageUrl(
        ?string $path,
    ): ?string {
        if ($path === null || $path === '') {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }
}
