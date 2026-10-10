<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductIndexQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductSearchSuggestionQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorefrontProductFilterRequest;
use App\Http\Requests\Frontend\StorefrontProductSearchSuggestionRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly StorefrontProductDetailQuery $productDetail,
    ) {}

    public function index(
        StorefrontProductFilterRequest $request,
        StorefrontProductIndexQuery $query,
        StorefrontFilterOptionsQuery $filterOptions,
    ): Response {
        $filters = $request->filters();

        $search = trim(
            $request
                ->string('q')
                ->toString(),
        );

        return Inertia::render('Frontend/Products/Index', [
            'products' => $query->paginate(
                filters: $filters,
                search: $search !== ''
                ? $search
                : null,
            ),

            'filters' => [
                'q' => $search,
                'sort' => $filters->sort->value,
                'brand' => $filters->brand,
                'category' => $filters->category,
                'min_price' => $filters->minPrice,
                'max_price' => $filters->maxPrice,
                'attributes' => $filters->attributes,
            ],

            'filterOptions' => $filterOptions
                ->get()
                ->toArray(),
        ]);
    }

    public function searchSuggestions(
        StorefrontProductSearchSuggestionRequest $request,
        StorefrontProductSearchSuggestionQuery $query,
    ): JsonResponse {
        return response()->json([
            'data' => $query
                ->get(
                    $request->search(),
                )
                ->values()
                ->all(),
        ]);
    }

    public function show(
        string $slug,
    ): Response {
        $product = $this->productDetail
            ->findBySlug($slug);

        abort_if(
            $product === null,
            404,
        );

        return Inertia::render(
            'Frontend/Products/Show',
            [
                'product' => $product->toArray(),
            ],
        );
    }
}
