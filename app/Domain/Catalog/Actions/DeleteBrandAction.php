<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Brand;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Media\ImageStorageService;
use Illuminate\Support\Facades\DB;

final readonly class DeleteBrandAction
{
    public function __construct(
        private ImageStorageService $imageStorage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(Brand $brand): void
    {
        $logoPath = $brand->logo_path;

        DB::transaction(
            static function () use ($brand): void {
                $brand->delete();
            },
        );

        $this->storefrontCache->invalidate();

        $this->imageStorage->delete(
            $logoPath,
        );
    }
}
