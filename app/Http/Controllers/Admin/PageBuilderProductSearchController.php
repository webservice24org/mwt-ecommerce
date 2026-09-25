<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Data\CatalogProductOptionData;
use App\Domain\PageBuilder\Queries\SearchCatalogProductOptionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchPageBuilderProductsRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

final class PageBuilderProductSearchController extends Controller
{
    public function __invoke(
        SearchPageBuilderProductsRequest $request,
        Page $page,
        SearchCatalogProductOptionsQuery $query,
    ): JsonResponse {
        $this->authorize(
            'update',
            $page,
        );

        return response()->json([
            'products' => array_map(
                static fn (
                    CatalogProductOptionData $product,
                ): array => $product->toArray(),
                $query->handle(
                    $request->search(),
                ),
            ),
        ]);
    }
}
