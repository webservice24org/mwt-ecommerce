<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapCache;
use App\Domain\Seo\Sitemap\Services\SitemapCacheKey;
use App\Domain\Seo\Sitemap\Services\SitemapCacheTtl;
use App\Domain\Seo\Sitemap\Services\SitemapPlanner;
use App\Domain\Seo\Sitemap\Services\SitemapPublicUrlResolver;
use App\Domain\Seo\Sitemap\Services\SitemapXmlRenderer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function index(
        SitemapPlanner $planner,
        SitemapXmlRenderer $renderer,
        SitemapPublicUrlResolver $publicUrl,
        SeoSettingsService $seoSettings,
        SitemapCache $cache,
        SitemapCacheTtl $cacheTtl,
    ): Response {
        $settings =
            $seoSettings->get();

        abort_unless(
            $settings->enableSitemap,
            404,
        );

        $xml =
            $cache->remember(
                key: SitemapCacheKey::index(),

                callback: fn (): string => $renderer->renderIndex(
                    entries: $planner
                        ->indexEntries(),

                    baseUrl: $publicUrl
                        ->baseUrl(
                            $settings,
                        ),
                ),

                ttlSeconds: $cacheTtl
                    ->seconds(),
            );

        return $this->xmlResponse(
            $xml,
        );
    }

    public function show(
        string $type,
        int $part,
        SitemapPlanner $planner,
        SitemapXmlRenderer $renderer,
        SeoSettingsService $seoSettings,
        SitemapCache $cache,
        SitemapCacheTtl $cacheTtl,
    ): Response {
        $settings =
            $seoSettings->get();

        abort_unless(
            $settings->enableSitemap,
            404,
        );

        $sitemapType =
            SitemapType::tryFrom(
                $type,
            );

        abort_if(
            $sitemapType === null,
            404,
        );

        $xml =
            $cache->rememberNotNull(
                key: SitemapCacheKey::part(
                    type: $sitemapType,

                    partNumber: $part,
                ),

                callback: function () use ($planner, $renderer, $sitemapType, $part): ?string {
                    $sitemapPart =
                        $planner->part(
                            type: $sitemapType,

                            partNumber: $part,
                        );

                    if (
                        $sitemapPart === null
                    ) {
                        return null;
                    }

                    return $renderer
                        ->renderPart(
                            $sitemapPart,
                        );
                },

                ttlSeconds: $cacheTtl
                    ->seconds(),
            );

        abort_if(
            $xml === null,
            404,
        );

        return $this->xmlResponse(
            $xml,
        );
    }

    private function xmlResponse(
        string $xml,
    ): Response {
        return response(
            content: $xml,

            status: 200,

            headers: [
                'Content-Type' => 'application/xml; charset=UTF-8',

                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
