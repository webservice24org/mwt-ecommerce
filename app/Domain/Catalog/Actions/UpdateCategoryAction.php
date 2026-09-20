<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\UpdateCategoryData;
use App\Domain\Catalog\Services\CategoryHierarchyService;
use App\Models\Category;
use App\Support\Cache\StorefrontCatalogCache;
use App\Support\Media\ImageStorageService;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateCategoryAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private CategoryHierarchyService $hierarchyService,
        private ImageStorageService $imageStorage,
        private StorefrontCatalogCache $storefrontCache,
    ) {}

    public function execute(
        Category $category,
        UpdateCategoryData $data,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): Category {
        $oldImagePath = $category->image_path;
        $newImagePath = null;

        try {
            if ($image !== null) {
                $newImagePath = $this->imageStorage->store(
                    image: $image,
                    directory: 'catalog/categories',
                );

                $finalImagePath = $newImagePath;
            } elseif ($removeImage) {
                $finalImagePath = null;
            } else {
                $finalImagePath = $oldImagePath;
            }

            $updatedCategory = DB::transaction(
                function () use (
                    $category,
                    $data,
                    $finalImagePath,
                ): Category {
                    $this->hierarchyService
                        ->ensureCanAssignParent(
                            category: $category,
                            parentId: $data->parentId,
                        );

                    $slugSource =
                        $data->slug ?: $data->name;

                    $slug = $this->slugGenerator
                        ->generate(
                            table: 'categories',
                            value: $slugSource,
                            ignoreId: $category->id,
                        );

                    $category->update([
                        'parent_id' => $data->parentId,
                        'name' => $data->name,
                        'slug' => $slug,
                        'description' => $data->description,
                        'image_path' => $finalImagePath,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'meta_title' => $data->metaTitle,
                        'meta_description' => $data->metaDescription,
                    ]);

                    return $category->refresh();
                },
            );
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                $this->imageStorage->delete(
                    $newImagePath,
                );
            }

            throw $exception;
        }

        $this->storefrontCache->invalidate();

        if (
            $oldImagePath !== null
            && $oldImagePath !== $updatedCategory->image_path
        ) {
            $this->imageStorage->delete(
                $oldImagePath,
            );
        }

        return $updatedCategory;
    }
}
