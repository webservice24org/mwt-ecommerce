<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsCache;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Admin;
use App\Models\SeoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoSettingsFoundationFinalRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
    }

    public function test_seo_settings_routes_are_registered_with_expected_methods(): void
    {
        $edit =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.settings.edit',
                );

        $update =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.settings.update',
                );

        $this->assertNotNull(
            $edit,
        );

        $this->assertNotNull(
            $update,
        );

        $this->assertContains(
            'GET',
            $edit->methods(),
        );

        $this->assertContains(
            'PUT',
            $update->methods(),
        );
    }

    public function test_guest_cannot_read_or_mutate_global_seo_settings(): void
    {
        $this
            ->get(
                route(
                    'admin.seo.settings.edit',
                ),
            )
            ->assertRedirect();

        $this
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $this->validPayload(),
            )
            ->assertRedirect();

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_admin_can_read_global_seo_settings_page(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.seo.settings.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Settings',
                    )
                    ->has(
                        'settings',
                    )
                    ->has(
                        'options.twitter_cards',
                        2,
                    )
                    ->where(
                        'options.title_tokens',
                        [
                            '{page_title}',
                            '{separator}',
                            '{site_name}',
                        ],
                    ),
            );
    }

    public function test_admin_update_persists_authoritative_typed_global_settings(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $this->validPayload(),
            )
            ->assertRedirect(
                route(
                    'admin.seo.settings.edit',
                ),
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
                'SEO settings updated successfully.',
            );

        $this->assertDatabaseCount(
            'seo_settings',
            1,
        );

        $setting =
            SeoSetting::query()
                ->firstOrFail();

        $this->assertSame(
            'MWT Store',
            $setting->site_name,
        );

        $this->assertSame(
            '|',
            $setting->title_separator,
        );

        $this->assertSame(
            '{page_title} {separator} {site_name}',
            $setting->default_title_template,
        );

        $this->assertSame(
            'https://shop.example.com',
            $setting->canonical_host,
        );

        $this->assertTrue(
            $setting->default_robots_index,
        );

        $this->assertTrue(
            $setting->default_robots_follow,
        );

        $this->assertTrue(
            $setting->enable_structured_data,
        );

        $this->assertTrue(
            $setting->enable_open_graph,
        );

        $this->assertTrue(
            $setting->enable_twitter_cards,
        );

        $this->assertTrue(
            $setting->enable_sitemap,
        );
    }

    public function test_repeated_updates_preserve_singleton_settings_record(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $first =
            $this->validPayload();

        $second =
            $this->validPayload();

        $second[
            'site_name'
        ] =
            'MWT Store Updated';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $first,
            )
            ->assertSessionHasNoErrors();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $second,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount(
            'seo_settings',
            1,
        );

        $this->assertSame(
            'MWT Store Updated',
            SeoSetting::query()
                ->firstOrFail()
                ->site_name,
        );
    }

    public function test_global_service_reads_cached_authoritative_settings(): void
    {
        $service =
            app(
                SeoSettingsService::class,
            );

        $service->save(
            $this->settingsData(
                siteName: 'Cached SEO Store',
            ),
        );

        $first =
            $service->get();

        $this->assertSame(
            'Cached SEO Store',
            $first->siteName,
        );

        $this->assertTrue(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );

        SeoSetting::query()
            ->firstOrFail()
            ->update([
                'site_name' => 'Database Direct Change',
            ]);

        /*
         * Cached application read remains stable until
         * explicit invalidation.
         */
        $this->assertSame(
            'Cached SEO Store',
            $service
                ->get()
                ->siteName,
        );

        $service->forget();

        $this->assertSame(
            'Database Direct Change',
            $service
                ->get()
                ->siteName,
        );
    }

    public function test_admin_update_invalidates_preexisting_global_cache(): void
    {
        $admin =
            Admin::factory()
                ->create();

        Cache::forever(
            SeoSettingsCache::KEY,
            $this->settingsData(
                siteName: 'Stale Cached Store',
            ),
        );

        $this->assertTrue(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $this->validPayload(),
            )
            ->assertSessionHasNoErrors();

        $this->assertFalse(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );

        $fresh =
            app(
                SeoSettingsService::class,
            )->get();

        $this->assertSame(
            'MWT Store',
            $fresh->siteName,
        );
    }

    public function test_invalid_cached_type_is_rejected_and_recovered_from_source_of_truth(): void
    {
        SeoSetting::query()
            ->create(
                $this->modelAttributes(
                    siteName: 'Recovered SEO Store',
                ),
            );

        Cache::forever(
            SeoSettingsCache::KEY,
            [
                'unexpected' => 'payload',
            ],
        );

        $settings =
            app(
                SeoSettingsService::class,
            )->get();

        $this->assertSame(
            'Recovered SEO Store',
            $settings->siteName,
        );

        $this->assertInstanceOf(
            SeoSettingsData::class,
            Cache::get(
                SeoSettingsCache::KEY,
            ),
        );
    }

    public function test_empty_database_uses_defaults_without_creating_hidden_row(): void
    {
        config([
            'app.name' => 'Default MWT Store',

            'app.url' => 'https://default.example.com',
        ]);

        $settings =
            app(
                SeoSettingsService::class,
            )->get();

        $this->assertSame(
            'Default MWT Store',
            $settings->siteName,
        );

        $this->assertSame(
            'https://default.example.com',
            $settings->canonicalHost,
        );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );

        $this->assertTrue(
            Cache::has(
                SeoSettingsCache::KEY,
            ),
        );
    }

    public function test_canonical_host_rejects_paths(): void
    {
        $this->assertInvalidField(
            field: 'canonical_host',

            value: 'https://example.com/shop',
        );
    }

    public function test_canonical_host_rejects_query_strings(): void
    {
        $this->assertInvalidField(
            field: 'canonical_host',

            value: 'https://example.com?campaign=test',
        );
    }

    public function test_canonical_host_rejects_fragments(): void
    {
        $this->assertInvalidField(
            field: 'canonical_host',

            value: 'https://example.com#store',
        );
    }

    public function test_canonical_host_rejects_embedded_credentials(): void
    {
        $this->assertInvalidField(
            field: 'canonical_host',

            value: 'https://user:password@example.com',
        );
    }

    public function test_canonical_host_rejects_non_http_protocol(): void
    {
        $this->assertInvalidField(
            field: 'canonical_host',

            value: 'ftp://example.com',
        );
    }

    public function test_valid_canonical_host_may_include_port(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'canonical_host'
        ] =
            'https://example.com:8443';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_settings',
            [
                'canonical_host' => 'https://example.com:8443',
            ],
        );
    }

    public function test_title_template_requires_page_title_token(): void
    {
        $this->assertInvalidField(
            field: 'default_title_template',

            value: '{site_name}',
        );
    }

    public function test_title_template_rejects_unknown_tokens(): void
    {
        $this->assertInvalidField(
            field: 'default_title_template',

            value: '{page_title} {separator} {unknown_token}',
        );
    }

    public function test_supported_title_tokens_are_accepted(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'default_title_template'
        ] =
            '{page_title} {separator} {site_name}';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_twitter_card_is_rejected(): void
    {
        $this->assertInvalidField(
            field: 'twitter_card',

            value: 'large_card_unknown',
        );
    }

    public function test_invalid_default_open_graph_url_is_rejected(): void
    {
        $this->assertInvalidField(
            field: 'default_og_image',

            value: 'not-a-url',
        );
    }

    public function test_invalid_organization_logo_url_is_rejected(): void
    {
        $this->assertInvalidField(
            field: 'organization_logo',

            value: 'logo.png',
        );
    }

    public function test_invalid_social_profile_url_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'organization_social_profiles'
        ] = [
            'https://facebook.com/mwt',
            'invalid-social-url',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'organization_social_profiles.1',
            );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_social_profiles_are_trimmed_deduplicated_and_blank_values_removed(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'organization_social_profiles'
        ] = [
            ' https://facebook.com/mwt ',
            '',
            'https://facebook.com/mwt',
            ' https://linkedin.com/company/mwt ',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $setting =
            SeoSetting::query()
                ->firstOrFail();

        $this->assertSame(
            [
                'https://facebook.com/mwt',
                'https://linkedin.com/company/mwt',
            ],
            $setting
                ->organization_social_profiles,
        );
    }

    public function test_more_than_twenty_social_profiles_are_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'organization_social_profiles'
        ] =
            array_map(
                static fn (
                    int $index,
                ): string => sprintf(
                    'https://example.com/profile-%d',
                    $index,
                ),

                range(
                    1,
                    21,
                ),
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'organization_social_profiles',
            );
    }

    public function test_required_feature_switches_cannot_be_omitted(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        unset(
            $payload[
                'default_robots_index'
            ],
            $payload[
                'default_robots_follow'
            ],
            $payload[
                'enable_structured_data'
            ],
            $payload[
                'enable_open_graph'
            ],
            $payload[
                'enable_twitter_cards'
            ],
            $payload[
                'enable_sitemap'
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors([
                'default_robots_index',
                'default_robots_follow',
                'enable_structured_data',
                'enable_open_graph',
                'enable_twitter_cards',
                'enable_sitemap',
            ]);

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_all_global_seo_feature_switches_can_be_explicitly_disabled(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'default_robots_index'
        ] =
            false;

        $payload[
            'default_robots_follow'
        ] =
            false;

        $payload[
            'enable_structured_data'
        ] =
            false;

        $payload[
            'enable_open_graph'
        ] =
            false;

        $payload[
            'enable_twitter_cards'
        ] =
            false;

        $payload[
            'enable_sitemap'
        ] =
            false;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $setting =
            SeoSetting::query()
                ->firstOrFail();

        $this->assertFalse(
            $setting->default_robots_index,
        );

        $this->assertFalse(
            $setting->default_robots_follow,
        );

        $this->assertFalse(
            $setting->enable_structured_data,
        );

        $this->assertFalse(
            $setting->enable_open_graph,
        );

        $this->assertFalse(
            $setting->enable_twitter_cards,
        );

        $this->assertFalse(
            $setting->enable_sitemap,
        );
    }

    public function test_seo_settings_page_contains_current_settings_workspace_contract(): void
    {
        $path =
            resource_path(
                'js/Pages/Admin/Seo/Settings.tsx',
            );

        $source =
            file_get_contents(
                $path,
            );

        $this->assertIsString(
            $source,
        );

        foreach (
            [
                'SEO Settings',
                'Site identity',
                'Search metadata defaults',
                'Canonical & indexing defaults',
                'Social sharing defaults',
                'Organization identity',
                'Search engine verification',
                'SEO feature controls',
                'AI SEO',
                'admin.seo.settings.update',
            ] as $expected
        ) {
            $this->assertStringContainsString(
                $expected,
                $source,
            );
        }
    }

    public function test_admin_sidebar_contains_live_seo_settings_and_future_disabled_destinations(): void
    {
        $path =
            resource_path(
                'js/Components/Admin/AdminSidebar.tsx',
            );

        $source =
            file_get_contents(
                $path,
            );

        $this->assertIsString(
            $source,
        );

        foreach (
            [
                "label: 'SEO'",
                "label: 'SEO Settings'",
                "'admin.seo.settings.edit'",
                "match: '/admin/seo'",
                "label: 'SEO Overview'",
                "label: 'AI Suggestions'",
                "label: 'AI SEO'",
                "label: 'Technical Health'",
            ] as $expected
        ) {
            $this->assertStringContainsString(
                $expected,
                $source,
            );
        }
    }

    public function test_controller_uses_application_service_as_settings_write_boundary(): void
    {
        $path =
            app_path(
                'Http/Controllers/Admin/Seo/SeoSettingsController.php',
            );

        $source =
            file_get_contents(
                $path,
            );

        $this->assertIsString(
            $source,
        );

        $this->assertStringContainsString(
            'SeoSettingsService',
            $source,
        );

        $this->assertStringContainsString(
            '$settings->save(',
            $source,
        );

        $this->assertStringNotContainsString(
            'SeoSetting::query()',
            $source,
        );

        $this->assertStringNotContainsString(
            'SeoSettingsRepository',
            $source,
        );
    }

    private function assertInvalidField(
        string $field,
        mixed $value,
    ): void {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            $field
        ] =
            $value;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                $field,
            );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'site_name' => 'MWT Store',

            'title_separator' => '|',

            'default_title_template' => '{page_title} {separator} {site_name}',

            'default_meta_description' => 'Shop quality products from MWT Store.',

            'canonical_host' => 'https://shop.example.com',

            'default_robots_index' => true,

            'default_robots_follow' => true,

            'default_og_image' => 'https://shop.example.com/images/default-og.jpg',

            'twitter_card' => 'summary_large_image',

            'organization_name' => 'MicroWeb Technology',

            'organization_logo' => 'https://shop.example.com/images/logo.png',

            'organization_social_profiles' => [
                'https://facebook.com/mwt',
                'https://linkedin.com/company/mwt',
            ],

            'google_site_verification' => 'google-verification-token',

            'bing_site_verification' => 'bing-verification-token',

            'enable_structured_data' => true,

            'enable_open_graph' => true,

            'enable_twitter_cards' => true,

            'enable_sitemap' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function modelAttributes(
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

    private function settingsData(
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
