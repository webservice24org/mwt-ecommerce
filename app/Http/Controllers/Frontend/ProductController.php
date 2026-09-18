<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductIndexQuery;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorefrontProductFilterRequest;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly StorefrontProductDetailQuery $productDetail,
        private readonly StorefrontProductDataFactory $productData,
    ) {}

    public function index(
        StorefrontProductFilterRequest $request,
        StorefrontProductIndexQuery $query,
        StorefrontFilterOptionsQuery $filterOptions,
    ): Response {
        $filters = $request->filters();

        return Inertia::render('Frontend/Products/Index', [
            'products' => $query->paginate($filters),

            'filters' => [
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

    public function show(
        string $slug,
    ): Response {
        $product = $this->productDetail
            ->findBySlugOrFail($slug);

        return Inertia::render(
            'Frontend/Products/Show',
            [
                'product' => $this
                    ->productData
                    ->detail($product)
                    ->toArray(),
            ],
        );
    }
}
