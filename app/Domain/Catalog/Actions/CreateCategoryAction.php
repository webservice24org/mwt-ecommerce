<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateCategoryData;
use App\Domain\Catalog\Services\CategoryHierarchyService;
use App\Models\Category;
use App\Support\Media\ImageStorageService;
use App\Support\Slugs\UniqueSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateCategoryAction
{
    public function __construct(
        private UniqueSlugGenerator $slugGenerator,
        private CategoryHierarchyService $hierarchyService,
        private ImageStorageService $imageStorage,
    ) {}

    public function execute(
        CreateCategoryData $data,
        ?UploadedFile $image = null,
    ): Category {
        $imagePath = null;

        try {
            if ($image !== null) {
                $imagePath = $this->imageStorage->store(
                    image: $image,
                    directory: 'catalog/categories',
                );
            }

            return DB::transaction(
                function () use (
                    $data,
                    $imagePath,
                ): Category {
                    $this->hierarchyService
                        ->ensureParentExists(
                            $data->parentId,
                        );

                    $slugSource =
                        $data->slug ?: $data->name;

                    $slug = $this->slugGenerator
                        ->generate(
                            table: 'categories',
                            value: $slugSource,
                        );

                    return Category::query()->create([
                        'parent_id' => $data->parentId,
                        'name' => $data->name,
                        'slug' => $slug,
                        'description' => $data->description,
                        'image_path' => $imagePath,
                        'position' => $data->position,
                        'is_active' => $data->isActive,
                        'meta_title' => $data->metaTitle,
                        'meta_description' => $data->metaDescription,
                    ]);
                },
            );
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                $this->imageStorage->delete(
                    $imagePath,
                );
            }

            throw $exception;
        }
    }
}
