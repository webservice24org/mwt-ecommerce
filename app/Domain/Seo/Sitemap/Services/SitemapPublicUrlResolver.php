<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use InvalidArgumentException;

final readonly class SitemapPublicUrlResolver
{
    public function baseUrl(
        SeoSettingsData $settings,
    ): string {
        $candidate =
            trim(
                (string) (
                    $settings
                        ->canonicalHost
                    ?? config(
                        'app.url',
                        '',
                    )
                ),
            );

        $candidate =
            rtrim(
                $candidate,
                '/',
            );

        if (
            $candidate === ''
            || filter_var(
                $candidate,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'A valid absolute sitemap base URL is required.',
            );
        }

        $scheme =
            strtolower(
                (string) parse_url(
                    $candidate,
                    PHP_URL_SCHEME,
                ),
            );

        if (
            $scheme !== 'http'
            && $scheme !== 'https'
        ) {
            throw new InvalidArgumentException(
                'Sitemap base URL must use HTTP or HTTPS.',
            );
        }

        return $candidate;
    }
}
