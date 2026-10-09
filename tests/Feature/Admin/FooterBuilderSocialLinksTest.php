<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\Admin;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterBuilderSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_links_can_be_updated_and_order_is_preserved(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config['social_links'] = [
            [
                'platform' => 'instagram',

                'url' => 'https://instagram.com/example',
            ],
            [
                'platform' => 'facebook',

                'url' => 'https://facebook.com/example',
            ],
            [
                'platform' => 'youtube',

                'url' => '/youtube',
            ],
            [
                'platform' => 'x',

                'url' => '#social',
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $storedConfig =
            $footer->getAttribute(
                'config',
            );

        $this->assertIsArray(
            $storedConfig,
        );

        $this->assertSame(
            $config[
                'social_links'
            ],
            $storedConfig[
                'social_links'
            ],
        );
    }

    public function test_footer_may_be_saved_without_social_links(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'social_links'
        ] = [];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            );

        $footer =
            FooterSetting::query()
                ->firstOrFail();

        $storedConfig =
            $footer->getAttribute(
                'config',
            );

        $this->assertIsArray(
            $storedConfig,
        );

        $this->assertSame(
            [],
            $storedConfig[
                'social_links'
            ],
        );
    }

    public function test_more_than_eight_social_links_are_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'social_links'
        ] =
            array_fill(
                0,
                9,
                [
                    'platform' => 'facebook',

                    'url' => '#',
                ],
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'social_links',
            ]);
    }

    public function test_unsupported_social_platform_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'myspace',

                'url' => 'https://example.com',
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'social_links.0.platform',
            ]);
    }

    public function test_unsafe_social_url_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'social_links'
        ] = [
            [
                'platform' => 'facebook',

                'url' => 'javascript:alert(1)',
            ],
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->put(
                route(
                    'admin.website-settings.footer-builder.update',
                ),
                [
                    'template' => FooterTemplate::LuxeNewsletter
                        ->value,

                    'is_enabled' => true,

                    'config' => $config,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.website-settings.footer-builder.edit',
                ),
            )
            ->assertSessionHasErrors([
                'social_links.0.url',
            ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()->create([
            'role' => AdminRole::Admin,

            'is_active' => true,
        ]);
    }
}
