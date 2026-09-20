<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateBrandData;
use App\Models\Brand;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Media\ImageStorageService;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateBrandAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private ImageStorageService $imageStorage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        Brand $brand,
        UpdateBrandData $data,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Brand {
        $oldLogoPath = $brand->logo_path;
        $newLogoPath = null;

        $finalLogoPath = $oldLogoPath;

        try {
            if ($logo !== null) {
                $newLogoPath =
                    $this->imageStorage->store(
                        $logo,
                        'catalog/brands',
                    );

                $finalLogoPath = $newLogoPath;
            } elseif ($removeLogo) {
                $finalLogoPath = null;
            }

            $updatedBrand = DB::transaction(
                function () use (
                    $brand,
                    $data,
                    $finalLogoPath,
                ): Brand {
                    $slug = $this->slugGenerator->generate(
                        table: 'brands',
                        column: 'slug',
                        value: $data->slug ?? $data->name,
                        ignoreId: $brand->id,
                    );

                    $brand->update([
                        'name' => $data->name,
                        'slug' => $slug,
                        'description' => $data->description,
                        'logo_path' => $finalLogoPath,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'meta_title' => $data->metaTitle,
                        'meta_description' => $data->metaDescription,
                    ]);

                    return $brand->refresh();
                },
            );
        } catch (Throwable $exception) {
            if ($newLogoPath !== null) {
                $this->imageStorage->delete(
                    $newLogoPath,
                );
            }

            throw $exception;
        }

        /*
         * The database mutation has successfully committed.
         * Invalidate storefront caches before performing
         * best-effort cleanup of the previous logo.
         */
        $this->storefrontCache->invalidate();

        if (
            $oldLogoPath !== null
            && $oldLogoPath !== $updatedBrand->logo_path
        ) {
            $this->imageStorage->delete(
                $oldLogoPath,
            );
        }

        return $updatedBrand;
    }
}
