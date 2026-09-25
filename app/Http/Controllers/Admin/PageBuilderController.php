<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\PageBuilder\Data\CatalogCategoryOptionData;
use App\Domain\PageBuilder\Data\CatalogProductOptionData;
use App\Domain\PageBuilder\Data\PageDataFactory;
use App\Domain\PageBuilder\Data\PageSectionData;
use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Queries\GetCatalogCategoryOptionsQuery;
use App\Domain\PageBuilder\Queries\GetCatalogProductOptionsByIdsQuery;
use App\Domain\PageBuilder\Queries\GetCatalogSourceDefinitionsQuery;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Queries\PageEditQuery;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

final class PageBuilderController extends Controller
{
    public function __invoke(
        Page $page,
        PageEditQuery $query,
        GetSectionDefinitionsQuery $sectionDefinitions,
        GetCatalogSourceDefinitionsQuery $catalogSources,
        GetCatalogCategoryOptionsQuery $categoryOptions,
        GetCatalogProductOptionsByIdsQuery $productOptions,
        PageDataFactory $dataFactory,
    ): Response {
        $this->authorize(
            'update',
            $page,
        );

        $page = $query->findOrFail(
            $page->id,
        );

        $pageData = $dataFactory->fromModel(
            $page,
        );

        $manualProductIds =
            $this->manualProductIds(
                $pageData->sections,
            );

        return Inertia::render(
            'Admin/PageBuilder/Pages/Builder',
            [
                'page' => $pageData->toArray(),

                'sectionDefinitions' => array_map(
                    static fn (
                        SectionDefinitionData $definition,
                    ): array => $definition->toArray(),
                    $sectionDefinitions->handle(),
                ),

                'catalogSources' => $catalogSources->handle(),

                'categoryOptions' => array_map(
                    static fn (
                        CatalogCategoryOptionData $category,
                    ): array => $category->toArray(),
                    $categoryOptions->handle(),
                ),

                'selectedProductOptions' => array_map(
                    static fn (
                        CatalogProductOptionData $product,
                    ): array => $product->toArray(),
                    $productOptions->handle(
                        $manualProductIds,
                    ),
                ),
            ],
        );
    }

    /**
     * @param  list<PageSectionData>  $sections
     * @return list<int>
     */
    private function manualProductIds(
        array $sections,
    ): array {
        $ids = [];

        foreach ($sections as $section) {
            $source = $section->config['source'] ?? null;

            if (! is_array($source)) {
                continue;
            }

            if (
                ($source['type'] ?? null)
                !== CatalogSourceType::Manual->value
            ) {
                continue;
            }

            $productIds =
                $source['product_ids'] ?? [];

            if (! is_array($productIds)) {
                continue;
            }

            foreach ($productIds as $productId) {
                if (! is_int($productId)) {
                    continue;
                }

                $ids[$productId] = $productId;
            }
        }

        return array_values($ids);
    }
}
