<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class GlobalStructuredDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_receives_global_structured_data(): void
    {
        $this->saveSettings(
            enabled: true,
        );

        $this
            ->get(
                '/',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontStructuredData.@context',
                        'https://schema.org',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.0.@type',
                        'Organization',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.0.@id',
                        'https://example.com/#organization',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.1.@type',
                        'WebSite',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.1.@id',
                        'https://example.com/#website',
                    ),
            );
    }

    public function test_global_structured_data_is_null_when_disabled(): void
    {
        $this->saveSettings(
            enabled: false,
        );

        $this
            ->get(
                '/',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->where(
                    'storefrontStructuredData',
                    null,
                ),
            );
    }

    private function saveSettings(
        bool $enabled,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MicroWeb Technology',

                organizationLogo: 'https://example.com/logo.png',

                organizationSocialProfiles: [
                    'https://facebook.com/mwt',
                ],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: $enabled,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
