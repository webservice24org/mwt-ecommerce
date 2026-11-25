<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Repositories\SeoSettingsRepository;
use App\Models\SeoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoSettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_returns_defaults_when_no_settings_exist(): void
    {
        config([
            'app.name' => 'MWT Shop',

            'app.url' => 'https://shop.example.com',
        ]);

        $settings =
            app(
                SeoSettingsRepository::class,
            )->get();

        $this->assertSame(
            'MWT Shop',
            $settings->siteName,
        );

        $this->assertSame(
            'https://shop.example.com',
            $settings->canonicalHost,
        );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_repository_persists_global_seo_settings(): void
    {
        $repository =
            app(
                SeoSettingsRepository::class,
            );

        $saved =
            $repository->save(
                new SeoSettingsData(
                    siteName: 'MicroWeb Store',

                    titleSeparator: '–',

                    defaultTitleTemplate: '{page_title} {separator} {site_name}',

                    defaultMetaDescription: 'Quality products from MicroWeb Store.',

                    canonicalHost: 'https://example.com',

                    defaultRobotsIndex: true,

                    defaultRobotsFollow: true,

                    defaultOgImage: 'https://example.com/og/default.jpg',

                    twitterCard: 'summary_large_image',

                    organizationName: 'MicroWeb Technology',

                    organizationLogo: 'https://example.com/logo.png',

                    organizationSocialProfiles: [
                        'https://facebook.com/example',
                        'https://linkedin.com/company/example',
                    ],

                    googleSiteVerification: 'google-test-token',

                    bingSiteVerification: 'bing-test-token',

                    enableStructuredData: true,

                    enableOpenGraph: true,

                    enableTwitterCards: true,

                    enableSitemap: true,
                ),
            );

        $this->assertSame(
            'MicroWeb Store',
            $saved->siteName,
        );

        $this->assertSame(
            'https://example.com',
            $saved->canonicalHost,
        );

        $this->assertDatabaseCount(
            'seo_settings',
            1,
        );

        $record =
            SeoSetting::query()
                ->firstOrFail();

        $this->assertSame(
            'MicroWeb Technology',
            $record->organization_name,
        );

        $this->assertSame(
            [
                'https://facebook.com/example',
                'https://linkedin.com/company/example',
            ],
            $record->organization_social_profiles,
        );
    }

    public function test_repeated_save_updates_single_global_record(): void
    {
        $repository =
            app(
                SeoSettingsRepository::class,
            );

        $first =
            $this->settings(
                siteName: 'First Name',
            );

        $repository->save(
            $first,
        );

        $second =
            $this->settings(
                siteName: 'Updated Name',
            );

        $repository->save(
            $second,
        );

        $this->assertDatabaseCount(
            'seo_settings',
            1,
        );

        $this->assertSame(
            'Updated Name',
            $repository
                ->get()
                ->siteName,
        );
    }

    private function settings(
        string $siteName,
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: $siteName,

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: null,

            canonicalHost: 'https://example.com',

            defaultRobotsIndex: true,

            defaultRobotsFollow: true,

            defaultOgImage: null,

            twitterCard: 'summary_large_image',

            organizationName: $siteName,

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
}
