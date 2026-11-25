<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Data\Storefront\StorefrontCategoryDataFactory;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryProductQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Seo\Redirects\Services\SeoSlugRedirectHistory;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontIndexationPolicy;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Services\BreadcrumbStructuredDataResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorefrontProductFilterRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
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
        StorefrontSeoResolver $seo,
        SeoSettingsService $seoSettings,
        CanonicalUrlResolver $canonical,
        StorefrontIndexationPolicy $indexation,
        BreadcrumbStructuredDataResolver $breadcrumbs,
        SeoSlugRedirectHistory $slugRedirects,
    ): Response|RedirectResponse {
        /*
         * ==================================================
         * AUTHORITATIVE CATEGORY
         * ==================================================
         *
         * StorefrontCategoryQuery returns the active Category
         * model. The exact same model is used for both
         * storefront presentation and SEO resolution.
         */
        try {
            $category =
                $categoryQuery
                    ->findBySlugOrFail(
                        $slug,
                    );
        } catch (ModelNotFoundException $exception) {
            $redirect = $slugRedirects->redirectResponse(
                entityType: SeoSlugRedirectHistory::CATEGORY,
                oldSlug: $slug,
            );

            if ($redirect !== null) {
                return $redirect;
            }

            throw $exception;
        }

        /*
         * ==================================================
         * AUTHORITATIVE SEO
         * ==================================================
         */
        $settings =
            $seoSettings->get();

        $resolvedSeo =
            $seo->resolve(
                entity: $category,

                settings: $settings,
            );

        $resolvedSeo =
            $resolvedSeo
                ->withCanonicalUrl(
                    $canonical->resolve(
                        canonicalOverride: $resolvedSeo
                            ->canonicalOverride,

                        path: "/category/{$category->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
                    ),
                );

        $resolvedSeo =
            $resolvedSeo
                ->withRobotsIndex(
                    $resolvedSeo
                        ->robotsIndex
                    && $indexation
                        ->allowsListingIndexing(
                            $request->query(),
                        ),
                );

        /*
         * ==================================================
         * AUTHORITATIVE BREADCRUMB STRUCTURED DATA
         * ==================================================
         */
        $breadcrumbStructuredData =
            $breadcrumbs->resolve(
                settings: $settings,

                items: [
                    new BreadcrumbStructuredDataItemData(
                        name: 'Home',

                        path: '/',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        name: 'Shop',

                        path: '/products',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        name: $category
                            ->name,
                    ),
                ],
            );

        /*
         * ==================================================
         * PRODUCT FILTERS
         * ==================================================
         */
        $filters =
            $request->filters();

        return Inertia::render(
            'Frontend/Categories/Show',
            [
                'category' => $categoryData
                    ->detail(
                        $category,
                    )
                    ->toArray(),

                /*
                 * Existing category product listing and
                 * filtering behavior remains unchanged.
                 */
                'products' => $productQuery
                    ->paginate(
                        $category,
                        $filters,
                    ),

                'filters' => [
                    'sort' => $filters
                        ->sort
                        ->value,

                    'brand' => $filters
                        ->brand,

                    /*
                     * The category is fixed by the current
                     * storefront route, so category filtering
                     * remains disabled here.
                     */
                    'category' => null,

                    'min_price' => $filters
                        ->minPrice,

                    'max_price' => $filters
                        ->maxPrice,

                    'attributes' => $filters
                        ->attributes,
                ],

                'filterOptions' => $filterOptions
                    ->get(
                        category: $category,

                        includeCategories: false,
                    )
                    ->toArray(),

                /*
                 * Server-authoritative SEO payload.
                 */
                'seo' => $resolvedSeo
                    ->toArray(),

                'breadcrumbStructuredData' => $breadcrumbStructuredData
                    ?->toArray(),
            ],
        );
    }
}
