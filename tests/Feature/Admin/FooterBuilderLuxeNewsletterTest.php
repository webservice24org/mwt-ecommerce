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

final class FooterBuilderLuxeNewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_luxe_newsletter_configuration_can_be_updated(): void
    {
        $admin =
            $this->createAdmin();

        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $config['value_props'] = [
            [
                'icon' => 'truck',

                'title' => 'Fast Delivery',

                'description' => 'Delivered quickly and securely.',
            ],
        ];

        $config['newsletter'] = [
            'enabled' => true,

            'description' => 'Join our newsletter.',

            'placeholder' => 'Enter your email',

            'button_label' => 'Join Now',
        ];

        $config['payment_methods'] = [
            'VISA',
            'MC',
            'BKASH',
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
                'value_props'
            ],
            $storedConfig[
                'value_props'
            ],
        );

        $this->assertSame(
            $config[
                'newsletter'
            ],
            $storedConfig[
                'newsletter'
            ],
        );

        $this->assertSame(
            $config[
                'payment_methods'
            ],
            $storedConfig[
                'payment_methods'
            ],
        );
    }

    public function test_unsupported_value_prop_icon_is_rejected(): void
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

        $config['value_props'][0][
            'icon'
        ] =
            'invalid-icon';

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
                'value_props.0.icon',
            ]);
    }

    public function test_enabled_newsletter_requires_placeholder_and_button_label(): void
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

        $config['newsletter'] = [
            'enabled' => true,

            'description' => 'Join our newsletter.',

            'placeholder' => '',

            'button_label' => '',
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
                'newsletter.placeholder',
                'newsletter.button_label',
            ]);
    }

    public function test_more_than_eight_payment_methods_are_rejected(): void
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
            'payment_methods'
        ] =
            array_fill(
                0,
                9,
                'CARD',
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
                'payment_methods',
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
