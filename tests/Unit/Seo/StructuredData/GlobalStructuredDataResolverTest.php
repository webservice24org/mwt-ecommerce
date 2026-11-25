<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Services\GlobalStructuredDataResolver;
use Tests\TestCase;

final class GlobalStructuredDataResolverTest extends TestCase
{
    public function test_it_resolves_organization_and_website(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(),
            );

        self::assertNotNull(
            $document,
        );

        $data =
            $document->toArray();

        self::assertSame(
            'https://schema.org',
            $data['@context'],
        );

        self::assertCount(
            2,
            $data['@graph'],
        );

        self::assertSame(
            'Organization',
            $data['@graph'][0]['@type'],
        );

        self::assertSame(
            'https://example.com/#organization',
            $data['@graph'][0]['@id'],
        );

        self::assertSame(
            'MicroWeb Technology',
            $data['@graph'][0]['name'],
        );

        self::assertSame(
            'WebSite',
            $data['@graph'][1]['@type'],
        );

        self::assertSame(
            'https://example.com/#website',
            $data['@graph'][1]['@id'],
        );

        self::assertSame(
            [
                '@id' => 'https://example.com/#organization',
            ],
            $data['@graph'][1]['publisher'],
        );
    }

    public function test_relative_logo_uses_canonical_host(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    organizationLogo: '/storage/branding/logo.png',
                ),
            );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            'https://example.com/storage/branding/logo.png',
            $document
                ->toArray()['@graph'][0]['logo']['url'],
        );
    }

    public function test_invalid_social_profiles_are_omitted(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    organizationSocialProfiles: [
                        'https://facebook.com/mwt',
                        'javascript:alert(1)',
                        'ftp://example.com/profile',
                        '/relative-profile',
                        'https://facebook.com/mwt',
                    ],
                ),
            );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            [
                'https://facebook.com/mwt',
            ],
            $document
                ->toArray()['@graph'][0]['sameAs'],
        );
    }

    public function test_unsafe_logo_scheme_is_omitted(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    organizationLogo: 'javascript:alert(1)',
                ),
            );

        self::assertNotNull(
            $document,
        );

        self::assertArrayNotHasKey(
            'logo',
            $document
                ->toArray()['@graph'][0],
        );
    }

    public function test_organization_name_falls_back_to_site_name(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    organizationName: null,
                ),
            );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            'MWT Store',
            $document
                ->toArray()['@graph'][0]['name'],
        );
    }

    public function test_site_name_falls_back_to_organization_name(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    siteName: null,

                    organizationName: 'MicroWeb Technology',
                ),
            );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            'MicroWeb Technology',
            $document
                ->toArray()['@graph'][1]['name'],
        );
    }

    public function test_structured_data_can_be_globally_disabled(): void
    {
        $document =
            app(
                GlobalStructuredDataResolver::class,
            )->resolve(
                $this->settings(
                    enableStructuredData: false,
                ),
            );

        self::assertNull(
            $document,
        );
    }

    /**
     * @param  array<int, string>  $organizationSocialProfiles
     */
    private function settings(
        ?string $siteName = 'MWT Store',
        ?string $canonicalHost = 'https://example.com',
        ?string $organizationName = 'MicroWeb Technology',
        ?string $organizationLogo = 'https://example.com/logo.png',
        array $organizationSocialProfiles = [
            'https://facebook.com/mwt',
            'https://linkedin.com/company/mwt',
        ],
        bool $enableStructuredData = true,
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: $siteName,

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: 'Default store description.',

            canonicalHost: $canonicalHost,

            defaultRobotsIndex: true,

            defaultRobotsFollow: true,

            defaultOgImage: null,

            twitterCard: 'summary_large_image',

            organizationName: $organizationName,

            organizationLogo: $organizationLogo,

            organizationSocialProfiles: $organizationSocialProfiles,

            googleSiteVerification: null,

            bingSiteVerification: null,

            enableStructuredData: $enableStructuredData,

            enableOpenGraph: true,

            enableTwitterCards: true,

            enableSitemap: true,
        );
    }
}
