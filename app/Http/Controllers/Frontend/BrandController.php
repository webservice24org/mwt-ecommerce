<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Data\Storefront\StorefrontBrandDataFactory;
use App\Domain\Catalog\Queries\Storefront\StorefrontBrandProductQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontBrandQuery;
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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BrandController extends Controller
{
    public function show(
        Request $httpRequest,
        string $slug,
        StorefrontProductFilterRequest $request,
        StorefrontBrandQuery $brandQuery,
        StorefrontBrandProductQuery $productQuery,
        StorefrontBrandDataFactory $brandData,
        StorefrontFilterOptionsQuery $filterOptions,
        StorefrontSeoResolver $seo,
        SeoSettingsService $seoSettings,
        CanonicalUrlResolver $canonical,
        StorefrontIndexationPolicy $indexationPolicy,
        BreadcrumbStructuredDataResolver $breadcrumbs,
        SeoSlugRedirectHistory $slugRedirects,
    ): Response|RedirectResponse {
        /*
         * ==================================================
         * AUTHORITATIVE BRAND
         * ==================================================
         */
        try {
            $brand =
                $brandQuery
                    ->findBySlugOrFail(
                        $slug,
                    );
        } catch (ModelNotFoundException $exception) {
            $redirect =
                $slugRedirects
                    ->redirectResponse(
                        entityType: SeoSlugRedirectHistory::BRAND,

                        oldSlug: $slug,
                    );

            if ($redirect !== null) {
                return $redirect;
            }

            throw $exception;
        }

        /*
         * ==================================================
         * SEO SETTINGS
         * ==================================================
         */
        $settings =
            $seoSettings->get();

        /*
         * ==================================================
         * AUTHORITATIVE ENTITY SEO
         * ==================================================
         */
        $resolvedSeo =
            $seo->resolve(
                entity: $brand,

                settings: $settings,
            );

        /*
         * ==================================================
         * FINAL CANONICAL
         * ==================================================
         */
        $resolvedSeo =
            $resolvedSeo
                ->withCanonicalUrl(
                    $canonical->resolve(
                        canonicalOverride: $resolvedSeo
                            ->canonicalOverride,

                        path: "/brand/{$brand->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
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
                        name: $brand
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

        /*
         * ==================================================
         * ROUTE INDEXATION POLICY
         * ==================================================
         *
         * The base brand route may remain indexable.
         *
         * Sort/category/price/attribute/pagination variations
         * must only make it more restrictive.
         */
        $policyAllowsIndexing =
            $indexationPolicy
                ->allowsListingIndexing(
                    $httpRequest
                        ->query(),
                );

        if (
            ! $policyAllowsIndexing
            && $resolvedSeo->robotsIndex
        ) {
            $resolvedSeo =
                $resolvedSeo
                    ->withRobotsIndex(
                        false,
                    );
        }

        return Inertia::render(
            'Frontend/Brands/Show',
            [
                'brand' => $brandData
                    ->detail(
                        $brand,
                    )
                    ->toArray(),

                'products' => $productQuery
                    ->paginate(
                        $brand,
                        $filters,
                    ),

                'filters' => [
                    'sort' => $filters
                        ->sort
                        ->value,

                    /*
                     * Brand is fixed by the route.
                     */
                    'brand' => null,

                    'category' => $filters
                        ->category,

                    'min_price' => $filters
                        ->minPrice,

                    'max_price' => $filters
                        ->maxPrice,

                    'attributes' => $filters
                        ->attributes,
                ],

                'filterOptions' => $filterOptions
                    ->get(
                        includeBrands: false,
                    )
                    ->toArray(),

                'seo' => $resolvedSeo
                    ->toArray(),

                'breadcrumbStructuredData' => $breadcrumbStructuredData
                    ?->toArray(),
            ],
        );
    }
}
