<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateCategoryAction;
use App\Domain\Catalog\Actions\DeleteCategoryAction;
use App\Domain\Catalog\Actions\UpdateCategoryAction;
use App\Domain\Catalog\Exceptions\InvalidCategoryHierarchyException;
use App\Domain\Catalog\Queries\CategoryIndexQuery;
use App\Domain\Catalog\Queries\CategoryParentOptionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function index(
        Request $request,
        CategoryIndexQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            Category::class,
        );

        $search = trim(
            $request->string('search')->toString(),
        );

        $status = $request
            ->string('status')
            ->toString();

        $isActive = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $categories = $query
            ->paginate(
                search: $search !== ''
                    ? $search
                    : null,
                isActive: $isActive,
            )
            ->through(
                fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,

                    'parent' => $category->parent === null
                        ? null
                        : [
                            'id' => $category->parent->id,
                            'name' => $category->parent->name,
                        ],

                    'position' => $category->position,
                    'is_active' => $category->is_active,
                    'created_at' => $category->created_at?->toISOString(),
                ],
            );

        return Inertia::render(
            'Admin/Catalog/Categories/Index',
            [
                'categories' => $categories,

                'filters' => [
                    'search' => $search,
                    'status' => $status,
                ],
            ],
        );
    }

    public function create(
        CategoryParentOptionsQuery $parentOptions,
    ): Response {
        $this->authorize(
            'create',
            Category::class,
        );

        return Inertia::render(
            'Admin/Catalog/Categories/Create',
            [
                'parentCategories' => $parentOptions->forCreate(),
            ],
        );
    }

    public function store(
        StoreCategoryRequest $request,
        CreateCategoryAction $action,
    ): RedirectResponse {
        $this->authorize(
            'create',
            Category::class,
        );

        try {
            $action->execute(
                data: $request->toData(),
                image: $request->image(),
            );
        } catch (InvalidCategoryHierarchyException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return to_route(
            'admin.categories.index',
        )->with(
            'success',
            'Category created successfully.',
        );
    }

    public function edit(
        Category $category,
        CategoryParentOptionsQuery $parentOptions,
    ): Response {
        $this->authorize(
            'update',
            $category,
        );

        return Inertia::render(
            'Admin/Catalog/Categories/Edit',
            [
                'category' => [
                    'id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image_path' => $category->image_path,
                    'image_url' => $category->image_path !== null
                        ? asset(
                            'storage/'.$category->image_path,
                        )
                        : null,
                    'position' => $category->position,
                    'is_active' => $category->is_active,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                ],

                'parentCategories' => $parentOptions
                    ->forEdit($category),
            ],
        );
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        UpdateCategoryAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $category,
        );

        try {
            $action->execute(
                category: $category,
                data: $request->toData(),
                image: $request->image(),
                removeImage: $request->shouldRemoveImage(),
            );
        } catch (InvalidCategoryHierarchyException $exception) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return to_route(
            'admin.categories.index',
        )->with(
            'success',
            'Category updated successfully.',
        );
    }

    public function destroy(
        Category $category,
        DeleteCategoryAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $category,
        );

        $action->execute(
            $category,
        );

        return to_route(
            'admin.categories.index',
        )->with(
            'success',
            'Category deleted successfully.',
        );
    }
}
