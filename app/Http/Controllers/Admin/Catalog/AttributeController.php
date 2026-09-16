<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateAttributeAction;
use App\Domain\Catalog\Actions\DeleteAttributeAction;
use App\Domain\Catalog\Actions\UpdateAttributeAction;
use App\Domain\Catalog\Queries\AttributeIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreAttributeRequest;
use App\Http\Requests\Admin\Catalog\UpdateAttributeRequest;
use App\Models\ProductAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AttributeController extends Controller
{
    public function index(
        Request $request,
        AttributeIndexQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            ProductAttribute::class,
        );

        $search = $request
            ->string('search')
            ->trim()
            ->toString();

        $active = match (
            $request->query('status')
        ) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $attributes = $query->paginate(
            search: $search === ''
                ? null
                : $search,
            isActive: $active,
        );

        return Inertia::render(
            'Admin/Catalog/Attributes/Index',
            [
                'attributes' => $attributes->through(
                    static fn (
                        ProductAttribute $attribute,
                    ): array => [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'slug' => $attribute->slug,
                        'position' => $attribute->position,
                        'is_active' => $attribute->is_active,
                        'values_count' => $attribute
                            ->values_count,
                    ],
                ),

                'filters' => [
                    'search' => $search,
                    'status' => $request->query(
                        'status',
                        '',
                    ),
                ],
            ],
        );
    }

    public function create(): Response
    {
        $this->authorize(
            'create',
            ProductAttribute::class,
        );

        return Inertia::render(
            'Admin/Catalog/Attributes/Create',
        );
    }

    public function store(
        StoreAttributeRequest $request,
        CreateAttributeAction $action,
    ): RedirectResponse {
        $this->authorize(
            'create',
            ProductAttribute::class,
        );

        $attribute = $action->execute(
            $request->toData(),
        );

        return redirect()
            ->route(
                'admin.attributes.edit',
                $attribute,
            )
            ->with(
                'success',
                'Attribute created successfully.',
            );
    }

    public function edit(
        ProductAttribute $attribute,
    ): Response {
        $this->authorize(
            'update',
            $attribute,
        );

        $attribute->load('values');

        return Inertia::render(
            'Admin/Catalog/Attributes/Edit',
            [
                'attribute' => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'slug' => $attribute->slug,
                    'position' => $attribute->position,
                    'is_active' => $attribute->is_active,

                    'values' => $attribute
                        ->values
                        ->map(
                            static fn (
                                $value,
                            ): array => [
                                'id' => $value->id,
                                'name' => $value->name,
                                'slug' => $value->slug,
                                'position' => $value->position,
                                'is_active' => $value->is_active,
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            ],
        );
    }

    public function update(
        UpdateAttributeRequest $request,
        ProductAttribute $attribute,
        UpdateAttributeAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $attribute,
        );

        $action->execute(
            $attribute,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Attribute updated successfully.',
        );
    }

    public function destroy(
        ProductAttribute $attribute,
        DeleteAttributeAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $attribute,
        );

        $action->execute($attribute);

        return redirect()
            ->route(
                'admin.attributes.index',
            )
            ->with(
                'success',
                'Attribute deleted successfully.',
            );
    }
}
