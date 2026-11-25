<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Services\SeoSettingsCache;
use App\Models\Admin;
use App\Models\SeoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class AdminSeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
    }

    public function test_guest_cannot_view_seo_settings(): void
    {
        $this
            ->get(
                route(
                    'admin.seo.settings.edit',
                ),
            )
            ->assertRedirect();
    }

    public function test_guest_cannot_update_seo_settings(): void
    {
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

    public function test_admin_can_view_seo_settings(): void
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
                fn ($page) => $page
                    ->component(
                        'Admin/Seo/Settings',
                    )
                    ->has(
                        'settings',
                    )
                    ->has(
                        'options.twitter_cards',
                    )
                    ->has(
                        'options.title_tokens',
                    ),
            );
    }

    public function test_admin_can_update_seo_settings(): void
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
            ->assertSessionHas(
                'success',
                'SEO settings updated successfully.',
            );

        $this->assertDatabaseHas(
            'seo_settings',
            [
                'site_name' => 'MWT Store',

                'canonical_host' => 'https://shop.example.com',

                'twitter_card' => 'summary_large_image',

                'organization_name' => 'MicroWeb Technology',

                'default_robots_index' => true,

                'default_robots_follow' => true,

                'enable_structured_data' => true,

                'enable_open_graph' => true,

                'enable_twitter_cards' => true,

                'enable_sitemap' => true,
            ],
        );
    }

    public function test_update_invalidates_existing_seo_cache(): void
    {
        $admin =
            Admin::factory()
                ->create();

        Cache::forever(
            SeoSettingsCache::KEY,
            'stale-value',
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
    }

    public function test_canonical_host_must_not_contain_path(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'canonical_host'
        ] =
            'https://example.com/store';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.seo.settings.edit',
                ),
            )
            ->put(
                route(
                    'admin.seo.settings.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'canonical_host',
            );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_canonical_host_cannot_contain_embedded_credentials(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'canonical_host'
        ] =
            'https://user:password@example.com';

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
                'canonical_host',
            );

        $this->assertDatabaseCount(
            'seo_settings',
            0,
        );
    }

    public function test_title_template_requires_page_title_token(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'default_title_template'
        ] =
            '{site_name}';

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
                'default_title_template',
            );
    }

    public function test_title_template_rejects_unknown_tokens(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $payload =
            $this->validPayload();

        $payload[
            'default_title_template'
        ] =
            '{page_title} {unknown_token}';

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
                'default_title_template',
            );
    }

    public function test_social_profiles_are_trimmed_and_deduplicated(): void
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
            'https://facebook.com/mwt',
            '',
            'https://linkedin.com/company/mwt',
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

        $settings =
            SeoSetting::query()
                ->firstOrFail();

        $this->assertSame(
            [
                'https://facebook.com/mwt',
                'https://linkedin.com/company/mwt',
            ],
            $settings
                ->organization_social_profiles,
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

            'default_meta_description' => 'Shop quality products online.',

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
}
