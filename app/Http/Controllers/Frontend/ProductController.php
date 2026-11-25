<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductIndexQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductSearchSuggestionQuery;
use App\Domain\Catalog\Services\Storefront\StorefrontProductDataFactory;
use App\Domain\Reviews\Queries\StorefrontProductReviewAggregateQuery;
use App\Domain\Reviews\Queries\StorefrontProductReviewsQuery;
use App\Domain\Reviews\Queries\StorefrontProductReviewStateQuery;
use App\Domain\Seo\Redirects\Services\SeoSlugRedirectHistory;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontIndexationPolicy;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Services\AggregateRatingStructuredDataEnricher;
use App\Domain\Seo\StructuredData\Services\BreadcrumbStructuredDataResolver;
use App\Domain\Seo\StructuredData\Services\ProductStructuredDataResolver;
use App\Domain\Seo\StructuredData\Services\ProductVariantStructuredDataResolver;
use App\Domain\Seo\StructuredData\Services\StructuredDataDocumentMerger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorefrontProductFilterRequest;
use App\Http\Requests\Frontend\StorefrontProductSearchSuggestionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        SeoSettingsService $seoSettings,
        StorefrontIndexationPolicy $indexation,
    ): Response {
        $filters =
            $request->filters();

        $search =
            trim(
                $request
                    ->string(
                        'q',
                    )
                    ->toString(),
            );

        $settings =
            $seoSettings->get();

        /*
         * Global noindex always wins.
         *
         * Route policy may further restrict an otherwise
         * indexable listing.
         */
        $robotsIndex =
            $settings->defaultRobotsIndex
            && $indexation
                ->allowsListingIndexing(
                    $request->query(),
                );

        return Inertia::render(
            'Frontend/Products/Index',
            [
                'products' => $query->paginate(
                    filters: $filters,

                    search: $search !== ''
                    ? $search
                    : null,
                ),

                'filters' => [
                    'q' => $search,

                    'sort' => $filters
                        ->sort
                        ->value,

                    'brand' => $filters
                        ->brand,

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
                    ->get()
                    ->toArray(),

                'robots' => [
                    'index' => $robotsIndex,

                    'follow' => $settings
                        ->defaultRobotsFollow,
                ],
            ],
        );
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
        Request $request,
        string $slug,
        StorefrontProductDataFactory $productData,
        StorefrontProductReviewStateQuery $reviewState,
        StorefrontProductReviewsQuery $reviews,
        StorefrontProductReviewAggregateQuery $reviewAggregate,
        StorefrontSeoResolver $seo,
        SeoSettingsService $seoSettings,
        CanonicalUrlResolver $canonical,
        BreadcrumbStructuredDataResolver $breadcrumbs,
        ProductStructuredDataResolver $productStructuredData,
        ProductVariantStructuredDataResolver $productVariantStructuredData,
        StructuredDataDocumentMerger $structuredDataDocuments,
        AggregateRatingStructuredDataEnricher $aggregateRating,
        SeoSlugRedirectHistory $slugRedirects,
    ): Response|RedirectResponse {
        /*
         * ==================================================
         * AUTHORITATIVE PRODUCT
         * ==================================================
         */
        try {
            $product =
                $this
                    ->productDetail
                    ->findBySlugOrFail(
                        $slug,
                    );
        } catch (ModelNotFoundException $exception) {
            $redirect =
                $slugRedirects
                    ->redirectResponse(
                        entityType: SeoSlugRedirectHistory::PRODUCT,

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
         *
         * Resolve once and reuse for metadata, canonical,
         * and structured data.
         */
        $settings =
            $seoSettings->get();

        /*
         * ==================================================
         * AUTHORITATIVE SEO
         * ==================================================
         */
        $resolvedSeo =
            $seo->resolve(
                entity: $product,

                settings: $settings,
            );

        /*
         * ==================================================
         * FINAL CANONICAL
         * ==================================================
         */
        $canonicalUrl =
            $canonical->resolve(
                canonicalOverride: $resolvedSeo
                    ->canonicalOverride,

                path: "/products/{$product->slug}",

                canonicalHost: $settings
                    ->canonicalHost,
            );

        $resolvedSeo =
            $resolvedSeo
                ->withCanonicalUrl(
                    $canonicalUrl,
                );

        /*
         * ==================================================
         * STOREFRONT PRODUCT DATA
         * ==================================================
         *
         * Build once and reuse for both the Inertia payload
         * and the authoritative breadcrumb hierarchy.
         */
        $productDetail =
            $productData->detail(
                $product,
            );

        $primaryCategory =
            $productDetail
                ->categories[0]
            ?? null;

        /*
         * ==================================================
         * AUTHORITATIVE BREADCRUMB STRUCTURED DATA
         * ==================================================
         */
        $breadcrumbItems = [
            new BreadcrumbStructuredDataItemData(
                name: 'Home',

                path: '/',
            ),

            new BreadcrumbStructuredDataItemData(
                name: 'Shop',

                path: '/products',
            ),
        ];

        if ($primaryCategory !== null) {
            $breadcrumbItems[] =
                new BreadcrumbStructuredDataItemData(
                    name: $primaryCategory
                        ->name,

                    path: '/category/'
                    .$primaryCategory
                        ->slug,
                );
        }

        $breadcrumbItems[] =
            new BreadcrumbStructuredDataItemData(
                name: $productDetail
                    ->name,
            );

        $breadcrumbStructuredData =
            $breadcrumbs->resolve(
                settings: $settings,

                items: $breadcrumbItems,
            );

        $productStructuredDataDocument =
            $productDetail->type ===
                ProductType::Variable
                ? $productVariantStructuredData
                    ->resolve(
                        settings: $settings,

                        product: $productDetail,

                        canonicalUrl: $canonicalUrl,
                    )
                : $productStructuredData
                    ->resolve(
                        settings: $settings,

                        product: $productDetail,

                        canonicalUrl: $canonicalUrl,
                    );

        $pageStructuredData =
            $structuredDataDocuments
                ->merge(
                    $breadcrumbStructuredData,
                    $productStructuredDataDocument,
                );

        /*
         * ==================================================
         * APPROVED REVIEW AGGREGATE STRUCTURED DATA
         * ==================================================
         *
         * Product reviews are product-level in the current
         * reviews domain. Only approved reviews are eligible.
         *
         * Simple products receive aggregateRating on Product.
         * Variable products receive it once on ProductGroup.
         * Variant Product nodes are intentionally untouched.
         */
        $reviewAggregateData =
            $reviewAggregate->get(
                product: $product,
            );

        $structuredDataPayload =
            $aggregateRating->enrich(
                document: $pageStructuredData
                    ?->toArray(),

                aggregate: $reviewAggregateData,
            );

        $requestedVariantId =
            $request->integer(
                'variant',
            );

        $selectedVariantId =
            collect(
                $productDetail
                    ->variants,
            )
                ->contains(
                    static fn (
                        $variant,
                    ): bool => $variant->id ===
                        $requestedVariantId,
                )
            ? $requestedVariantId
            : null;

        /*
         * ==================================================
         * AUTHENTICATED REVIEW STATE
         * ==================================================
         */
        $user =
            $request->user();

        return Inertia::render(
            'Frontend/Products/Show',
            [
                'product' => $productDetail
                    ->toArray(),

                'reviewState' => $reviewState
                    ->get(
                        user: $user instanceof User
                        ? $user
                        : null,

                        product: $product,
                    ),

                'reviews' => $reviews
                    ->get(
                        product: $product,
                    ),

                'seo' => $resolvedSeo
                    ->toArray(),

                'structuredData' => $structuredDataPayload,

                'selectedVariantId' => $selectedVariantId,
            ],
        );
    }
}
