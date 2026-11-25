<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data\Storefront;

use App\Models\Brand;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final readonly class StorefrontBrandDataFactory
{
    public function detail(
        Brand $brand,
    ): StorefrontBrandDetailData {
        return new StorefrontBrandDetailData(
            id: $brand->id,

            name: $brand->name,

            slug: $brand->slug,

            description: $brand->description,

            logoUrl: $this->logoUrl(
                $brand->logo_path,
            ),

            metaTitle: $brand->meta_title,

            metaDescription: $brand->meta_description,
        );
    }

    private function logoUrl(
        ?string $path,
    ): ?string {
        if (
            $path === null
            || trim($path) === ''
        ) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public',
            );

        return $disk->url(
            $path,
        );
    }
}
