<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateBrandData;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Redirects\Services\SeoSlugRedirectHistory;
use App\Domain\Seo\Repositories\EntitySeoRepository;
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
        private ?EntitySeoRepository $seoRepository = null,
        private ?SeoSlugRedirectHistory $slugRedirects = null,
    ) {}

    public function execute(
        Brand $brand,
        UpdateBrandData $data,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
        ?EntitySeoData $seo = null,
    ): Brand {
        $oldLogoPath = $brand->logo_path;
        $newLogoPath = null;
        $finalLogoPath = $oldLogoPath;

        try {
            if ($logo !== null) {
                $newLogoPath = $this->imageStorage->store(
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
                    $seo,
                ): Brand {
                    $lockedBrand = Brand::query()
                        ->whereKey($brand->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $oldSlug = $lockedBrand->slug;

                    $slug = $this->slugGenerator->generate(
                        table: 'brands',
                        column: 'slug',
                        value: $data->slug ?? $data->name,
                        ignoreId: $lockedBrand->id,
                    );

                    $lockedBrand->update([
                        'name' => $data->name,
                        'slug' => $slug,
                        'description' => $data->description,
                        'logo_path' => $finalLogoPath,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'meta_title' => $data->metaTitle,
                        'meta_description' => $data->metaDescription,
                    ]);

                    if ($seo !== null) {
                        ($this->seoRepository
                            ?? app(EntitySeoRepository::class))
                            ->save(
                                $lockedBrand,
                                $seo,
                            );
                    }

                    ($this->slugRedirects
                        ?? app(SeoSlugRedirectHistory::class))
                        ->record(
                            entityType: SeoSlugRedirectHistory::BRAND,
                            entityId: $lockedBrand->id,
                            oldSlug: $oldSlug,
                            newSlug: $slug,
                        );

                    return $lockedBrand->refresh();
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

        if (
            $oldLogoPath !== null
            && $oldLogoPath !== $updatedBrand->logo_path
        ) {
            $this->imageStorage->delete(
                $oldLogoPath,
            );
        }

        $this->storefrontCache->invalidate();

        return $updatedBrand;
    }
}
