<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Services\ProductImageStorageService;
use App\Domain\Catalog\Services\ProductVideoStorageService;
use App\Models\Product;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Support\Facades\DB;

final class DeleteProductAction
{
    public function __construct(
        private readonly ProductImageStorageService $imageStorage,
        private readonly ProductVideoStorageService $videoStorage,
        private readonly StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(Product $product): void
    {
        $product->loadMissing([
            'images:id,product_id,path',
            'video:id,product_id,path',
        ]);

        $imagePaths = $product->images
            ->pluck('path')
            ->filter(
                static fn (?string $path): bool => filled($path),
            )
            ->values()
            ->all();

        $videoPath = $product->video?->path;

        DB::transaction(
            static function () use ($product): void {
                $product->delete();
            },
        );

        $this->storefrontCache->invalidate();

        foreach ($imagePaths as $imagePath) {
            $this->imageStorage->delete(
                $imagePath,
            );
        }

        $this->videoStorage->delete(
            $videoPath,
        );
    }
}
