<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Category;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Media\ImageStorageService;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCategoryAction
{
    public function __construct(
        private ImageStorageService $imageStorage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        Category $category,
    ): void {
        $imagePath = $category->image_path;

        DB::transaction(
            function () use ($category): void {
                $category->delete();
            },
        );

        $this->storefrontCache->invalidate();

        $this->imageStorage->delete(
            $imagePath,
        );
    }
}
