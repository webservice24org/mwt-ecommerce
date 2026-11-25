<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Seo\Services\SeoSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

final class RobotsTxtController extends Controller
{
    public function __invoke(
        SeoSettingsService $seoSettings,
    ): Response {
        $settings =
            $seoSettings->get();

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /checkout',
        ];

        if (
            $settings
                ->enableSitemap
        ) {
            $host =
                $settings
                    ->canonicalHost;

            $sitemapUrl =
                is_string(
                    $host,
                )
                && trim(
                    $host,
                ) !== ''
                    ? rtrim(
                        $host,
                        '/',
                    )
                    .'/sitemap.xml'
                    : url(
                        '/sitemap.xml',
                    );

            $lines = [
                ...$lines,
                '',
                'Sitemap: '
                .$sitemapUrl,
            ];
        }

        return response(
            implode(
                "\n",
                $lines,
            )
            ."\n",
            200,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',

                'Cache-Control' => 'public, max-age=300',
            ],
        );
    }
}
