<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\PageBuilder\Queries\StorefrontPageQuery;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Services\StorefrontSeoResolver;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Services\BreadcrumbStructuredDataResolver;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class PageController extends Controller
{
    public function __invoke(
        string $slug,
        StorefrontPageQuery $query,
        StorefrontSeoResolver $seo,
        SeoSettingsService $seoSettings,
        CanonicalUrlResolver $canonical,
        BreadcrumbStructuredDataResolver $breadcrumbs,
    ): Response {
        $pageModel =
            $query
                ->findModelBySlugOrFail(
                    $slug,
                );

        $page =
            $query->data(
                $pageModel,
            );

        $settings =
            $seoSettings->get();

        $resolvedSeo =
            $seo->resolve(
                entity: $pageModel,

                settings: $settings,
            );

        $resolvedSeo =
            $resolvedSeo
                ->withCanonicalUrl(
                    $canonical->resolve(
                        canonicalOverride: $resolvedSeo
                            ->canonicalOverride,

                        path: "/pages/{$pageModel->slug}",

                        canonicalHost: $settings
                            ->canonicalHost,
                    ),
                );

        $breadcrumbStructuredData =
            $pageModel->show_breadcrumbs
                ? $breadcrumbs->resolve(
                    settings: $settings,

                    items: [
                        new BreadcrumbStructuredDataItemData(
                            name: 'Home',

                            path: '/',
                        ),

                        new BreadcrumbStructuredDataItemData(
                            name: $pageModel
                                ->title,
                        ),
                    ],
                )
                : null;

        return Inertia::render(
            'Frontend/Pages/Show',
            [
                'page' => $page
                    ->toArray(),

                'seo' => $resolvedSeo
                    ->toArray(),

                'breadcrumbStructuredData' => $breadcrumbStructuredData
                    ?->toArray(),
            ],
        );
    }
}
