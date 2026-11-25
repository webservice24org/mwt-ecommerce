<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\CreateCategoryData;
use App\Domain\Catalog\Services\CategoryHierarchyService;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Category;
use App\Support\Cache\StorefrontCatalogCache;
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
        private StorefrontCatalogCache $storefrontCache,
        private EntitySeoRepository $seoRepository,
    ) {}

    public function execute(
        CreateCategoryData $data,
        ?UploadedFile $image = null,
        ?EntitySeoData $seo = null,
    ): Category {
        $imagePath = null;

        try {
            if ($image !== null) {
                $imagePath = $this->imageStorage->store(
                    image: $image,
                    directory: 'catalog/categories',
                );
            }

            $category = DB::transaction(
                function () use ($data, $imagePath, $seo): Category {
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

                    $category = Category::query()->create([
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

                    if (
                        $seo !== null
                    ) {
                        $this
                            ->seoRepository
                            ->save(
                                $category,
                                $seo,
                            );
                    }

                    return $category
                        ->refresh()
                        ->load([
                            'seoMeta',
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

        $this->storefrontCache->invalidate();

        return $category;
    }
}
