<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateBrandData;
use App\Models\Brand;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Media\ImageStorageService;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateBrandAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private ImageStorageService $imageStorage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        CreateBrandData $data,
        ?UploadedFile $logo = null,
    ): Brand {
        $logoPath = null;

        try {
            if ($logo !== null) {
                $logoPath = $this->imageStorage->store(
                    $logo,
                    'catalog/brands',
                );
            }

            $brand = DB::transaction(
                function () use (
                    $data,
                    $logoPath,
                ): Brand {
                    $slug = $this->slugGenerator->generate(
                        table: 'brands',
                        column: 'slug',
                        value: $data->slug ?? $data->name,
                    );

                    return Brand::query()->create([
                        'name' => $data->name,
                        'slug' => $slug,
                        'description' => $data->description,
                        'logo_path' => $logoPath,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'meta_title' => $data->metaTitle,
                        'meta_description' => $data->metaDescription,
                    ]);
                },
            );
        } catch (Throwable $exception) {
            if ($logoPath !== null) {
                $this->imageStorage->delete(
                    $logoPath,
                );
            }

            throw $exception;
        }

        $this->storefrontCache->invalidate();

        return $brand;
    }
}
