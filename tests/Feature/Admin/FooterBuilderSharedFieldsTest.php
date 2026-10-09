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

final class FooterBuilderSharedFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_brand_and_copyright_fields_can_be_updated(): void
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

        $config['brand'] = [
            'name' => 'MWT Commerce',

            'description' => 'Modern commerce for modern customers.',
        ];

        $config['copyright'] = [
            'name' => 'MWT Commerce',

            'suffix' => 'All rights reserved.',
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

        $this->assertSame(
            'MWT Commerce',
            $footer->config[
                'brand'
            ]['name'],
        );

        $this->assertSame(
            'Modern commerce for modern customers.',
            $footer->config[
                'brand'
            ]['description'],
        );

        $this->assertSame(
            'MWT Commerce',
            $footer->config[
                'copyright'
            ]['name'],
        );

        $this->assertSame(
            'All rights reserved.',
            $footer->config[
                'copyright'
            ]['suffix'],
        );
    }

    public function test_brand_name_longer_than_schema_limit_is_rejected(): void
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

        $config['brand']['name'] =
            str_repeat(
                'A',
                161,
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
                'brand.name',
            ]);
    }

    public function test_copyright_fields_are_required(): void
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

        $config['copyright'] = [
            'name' => '',

            'suffix' => '',
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
                'copyright.name',
                'copyright.suffix',
            ]);
    }
}
