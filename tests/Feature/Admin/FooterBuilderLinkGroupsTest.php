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

final class FooterBuilderLinkGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_groups_can_be_updated_and_order_is_preserved(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config['link_groups'] = [
            [
                'heading' => 'Shop',

                'links' => [
                    [
                        'label' => 'New Arrivals',

                        'url' => '/products',
                    ],
                    [
                        'label' => 'Newsletter',

                        'url' => '#newsletter',
                    ],
                ],
            ],
            [
                'heading' => 'Company',

                'links' => [
                    [
                        'label' => 'About Us',

                        'url' => '/about',
                    ],
                    [
                        'label' => 'External Site',

                        'url' => 'https://example.com',
                    ],
                ],
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
                'link_groups'
            ],
            $storedConfig[
                'link_groups'
            ],
        );
    }

    public function test_footer_may_be_saved_without_link_groups(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'link_groups'
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
                'link_groups'
            ],
        );
    }

    public function test_more_than_four_link_groups_are_rejected(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);

        FooterSetting::singleton();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'link_groups'
        ] =
            array_fill(
                0,
                5,
                [
                    'heading' => 'Links',

                    'links' => [],
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
                'link_groups',
            ]);
    }
}
