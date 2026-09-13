<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateBrandAction;
use App\Domain\Catalog\Actions\DeleteBrandAction;
use App\Domain\Catalog\Actions\UpdateBrandAction;
use App\Domain\Catalog\Queries\BrandIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreBrandRequest;
use App\Http\Requests\Admin\Catalog\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BrandController extends Controller
{
    public function index(
        Request $request,
        BrandIndexQuery $query,
    ): Response {
        $this->authorize('viewAny', Brand::class);

        $search = trim(
            (string) $request->query('search', ''),
        );

        $status = $request->query('status');

        $isActive = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $brands = $query->paginate(
            search: $search !== '' ? $search : null,
            isActive: $isActive,
        );

        return Inertia::render(
            'Admin/Catalog/Brands/Index',
            [
                'brands' => $brands->through(
                    static fn (Brand $brand): array => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'logo_url' => $brand->logo_path !== null
                            ? asset(
                                'storage/'.$brand->logo_path,
                            )
                            : null,
                        'position' => $brand->position,
                        'is_active' => $brand->is_active,
                    ],
                ),

                'filters' => [
                    'search' => $search,
                    'status' => is_string($status)
                        ? $status
                        : '',
                ],
            ],
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Brand::class);

        return Inertia::render(
            'Admin/Catalog/Brands/Create',
        );
    }

    public function store(
        StoreBrandRequest $request,
        CreateBrandAction $action,
    ): RedirectResponse {
        $this->authorize('create', Brand::class);

        $action->execute(
            data: $request->toData(),
            logo: $request->logo(),
        );

        return to_route(
            'admin.brands.index',
        )->with(
            'success',
            'Brand created successfully.',
        );
    }

    public function edit(
        Brand $brand,
    ): Response {
        $this->authorize('update', $brand);

        return Inertia::render(
            'Admin/Catalog/Brands/Edit',
            [
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'logo_path' => $brand->logo_path,
                    'logo_url' => $brand->logo_path !== null
                        ? asset(
                            'storage/'.$brand->logo_path,
                        )
                        : null,
                    'position' => $brand->position,
                    'is_active' => $brand->is_active,
                    'meta_title' => $brand->meta_title,
                    'meta_description' => $brand->meta_description,
                ],
            ],
        );
    }

    public function update(
        UpdateBrandRequest $request,
        Brand $brand,
        UpdateBrandAction $action,
    ): RedirectResponse {
        $this->authorize('update', $brand);

        $action->execute(
            brand: $brand,
            data: $request->toData(),
            logo: $request->logo(),
            removeLogo: $request->shouldRemoveLogo(),
        );

        return to_route(
            'admin.brands.index',
        )->with(
            'success',
            'Brand updated successfully.',
        );
    }

    public function destroy(
        Brand $brand,
        DeleteBrandAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $brand);

        $action->execute($brand);

        return to_route(
            'admin.brands.index',
        )->with(
            'success',
            'Brand deleted successfully.',
        );
    }
}
