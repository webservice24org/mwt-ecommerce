<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsCache;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\SeoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SeoSettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
    }

    public function test_settings_are_cached_after_first_read(): void
    {
        SeoSetting::query()
            ->create(
                $this->attributes(
                    siteName: 'Cached Store',
                ),
            );

        $service =
            app(
                SeoSettingsService::class,
            );

        $first =
            $service->get();

        $this->assertSame(
            'Cached Store',
            $first->siteName,
        );

        SeoSetting::query()
            ->firstOrFail()
            ->update([
                'site_name' => 'Database Changed',
            ]);

        $second =
            $service->get();

        $this->assertSame(
            'Cached Store',
            $second->siteName,
        );
    }

    public function test_forget_forces_next_read_from_database(): void
    {
        SeoSetting::query()
            ->create(
                $this->attributes(
                    siteName: 'First Name',
                ),
            );

        $service =
            app(
                SeoSettingsService::class,
            );

        $this->assertSame(
            'First Name',
            $service
                ->get()
                ->siteName,
        );

        SeoSetting::query()
            ->firstOrFail()
            ->update([
                'site_name' => 'Updated Name',
            ]);

        $service->forget();

        $this->assertSame(
            'Updated Name',
            $service
                ->get()
                ->siteName,
        );
    }

    public function test_save_invalidates_existing_cache(): void
    {
        $service =
            app(
                SeoSettingsService::class,
            );

        $service->save(
            $this->data(
                siteName: 'Initial Name',
            ),
        );

        $this->assertSame(
            'Initial Name',
            $service
                ->get()
                ->siteName,
        );

        $saved =
            $service->save(
                $this->data(
                    siteName: 'Saved Name',
                ),
            );

        $this->assertSame(
            'Saved Name',
            $saved->siteName,
        );

        $this->assertSame(
            'Saved Name',
            $service
                ->get()
                ->siteName,
        );
    }

    public function test_refresh_reloads_and_repopulates_cache(): void
    {
        SeoSetting::query()
            ->create(
                $this->attributes(
                    siteName: 'Before Refresh',
                ),
            );

        $service =
            app(
                SeoSettingsService::class,
            );

        $service->get();

        SeoSetting::query()
            ->firstOrFail()
            ->update([
                'site_name' => 'After Refresh',
            ]);

        $settings =
            $service->refresh();

        $this->assertSame(
            'After Refresh',
            $settings->siteName,
        );

        $this->assertTrue(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );
    }

    public function test_invalid_cached_value_is_discarded(): void
    {
        SeoSetting::query()
            ->create(
                $this->attributes(
                    siteName: 'Recovered Store',
                ),
            );

        Cache::forever(
            SeoSettingsCache::KEY,
            'invalid-cache-value',
        );

        $settings =
            app(
                SeoSettingsService::class,
            )->get();

        $this->assertSame(
            'Recovered Store',
            $settings->siteName,
        );

        $this->assertInstanceOf(
            SeoSettingsData::class,
            Cache::get(
                SeoSettingsCache::KEY,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(
        string $siteName,
    ): array {
        return [
            'site_name' => $siteName,

            'title_separator' => '|',

            'default_title_template' => '{page_title} {separator} {site_name}',

            'default_meta_description' => null,

            'canonical_host' => 'https://example.com',

            'default_robots_index' => true,

            'default_robots_follow' => true,

            'default_og_image' => null,

            'twitter_card' => 'summary_large_image',

            'organization_name' => $siteName,

            'organization_logo' => null,

            'organization_social_profiles' => [],

            'google_site_verification' => null,

            'bing_site_verification' => null,

            'enable_structured_data' => true,

            'enable_open_graph' => true,

            'enable_twitter_cards' => true,

            'enable_sitemap' => true,
        ];
    }

    private function data(
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

    public function test_defaults_are_cached_when_database_has_no_row(): void
    {
        config([
            'app.name' => 'Default SEO Store',

            'app.url' => 'https://default.example.com',
        ]);

        $service =
            app(
                SeoSettingsService::class,
            );

        $first =
            $service->get();

        $this->assertSame(
            'Default SEO Store',
            $first->siteName,
        );

        $this->assertSame(
            'https://default.example.com',
            $first->canonicalHost,
        );

        $this->assertTrue(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }
}
