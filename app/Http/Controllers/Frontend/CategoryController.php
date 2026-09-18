<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Data\Storefront\StorefrontCategoryDataFactory;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryProductQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorefrontProductFilterRequest;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function show(
        string $slug,
        StorefrontProductFilterRequest $request,
        StorefrontCategoryQuery $categoryQuery,
        StorefrontCategoryProductQuery $productQuery,
        StorefrontCategoryDataFactory $categoryData,
        StorefrontFilterOptionsQuery $filterOptions,
    ): Response {
        $category = $categoryQuery
            ->findBySlugOrFail($slug);

        $filters = $request->filters();

        return Inertia::render(
            'Frontend/Categories/Show',
            [
                'category' => $categoryData
                    ->detail($category)
                    ->toArray(),

                'products' => $productQuery->paginate(
                    $category,
                    $filters,
                ),

                'filters' => [
                    'sort' => $filters->sort->value,
                    'brand' => $filters->brand,
                    'category' => null,
                    'min_price' => $filters->minPrice,
                    'max_price' => $filters->maxPrice,
                    'attributes' => $filters->attributes,
                ],

                'filterOptions' => $filterOptions
                    ->get(
                        category: $category,
                        includeCategories: false,
                    )
                    ->toArray(),
            ],
        );
    }
}
