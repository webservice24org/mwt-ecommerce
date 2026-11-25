<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\SeoSettingsData;

final class SeoSettingsDefaults
{
    public function make(): SeoSettingsData
    {
        return new SeoSettingsData(
            siteName: config(
                'app.name',
            ),

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: null,

            canonicalHost: $this->canonicalHost(),

            defaultRobotsIndex: true,

            defaultRobotsFollow: true,

            defaultOgImage: null,

            twitterCard: 'summary_large_image',

            organizationName: config(
                'app.name',
            ),

            organizationLogo: null,

            organizationSocialProfiles: [],

            googleSiteVerification: null,

            bingSiteVerification: null,

            enableStructuredData: true,

            enableOpenGraph: true,

            enableTwitterCards: true,

            enableSitemap: true,
        );
    }

    private function canonicalHost(): ?string
    {
        $url =
            trim(
                (string) config(
                    'app.url',
                    '',
                ),
            );

        if (
            $url === ''
        ) {
            return null;
        }

        $parts =
            parse_url(
                $url,
            );

        if (
            ! is_array(
                $parts,
            )
        ) {
            return null;
        }

        $scheme =
            $parts['scheme'] ??
            null;

        $host =
            $parts['host'] ??
            null;

        if (
            ! is_string(
                $scheme,
            ) ||
            ! is_string(
                $host,
            )
        ) {
            return null;
        }

        $canonical =
            strtolower(
                $scheme,
            ).
            '://'.
            strtolower(
                $host,
            );

        if (
            isset(
                $parts['port'],
            )
        ) {
            $canonical .=
                ':'.
                (int) $parts['port'];
        }

        return $canonical;
    }
}
