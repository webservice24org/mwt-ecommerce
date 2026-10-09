<?php

declare(strict_types=1);

namespace Tests\Unit\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\Exceptions\InvalidFooterConfiguration;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Domain\FooterBuilder\Registry\FooterTemplateRegistry;
use App\Domain\FooterBuilder\Schemas\FooterConfigSchema;
use PHPUnit\Framework\TestCase;

final class FooterBuilderDomainRegressionTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const CONFIG_KEYS = [
        'brand',
        'link_groups',
        'social_links',
        'copyright',
        'developer',
        'value_props',
        'newsletter',
        'payment_methods',
        'localization',
        'promotion',
        'popular_links',
        'certifications',
    ];

    public function test_footer_builder_v1_template_contract_is_stable(): void
    {
        $this->assertSame(
            [
                'luxe_newsletter',
                'minimal_localized',
                'marketplace_trust',
            ],
            array_map(
                static fn (
                    FooterTemplate $template,
                ): string => $template->value,
                FooterTemplate::cases(),
            ),
        );
    }

    public function test_registry_supports_every_footer_builder_v1_template(): void
    {
        $registry =
            new FooterTemplateRegistry;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $this->assertTrue(
                $registry->supports(
                    $template->value,
                ),
                sprintf(
                    'Expected footer template [%s] to be registered.',
                    $template->value,
                ),
            );

            $registered =
                $registry->get(
                    $template,
                );

            $this->assertSame(
                $template,
                $registered->template,
            );

            $this->assertSame(
                $template->value,
                $registered->key,
            );

            $this->assertNotSame(
                '',
                trim(
                    $registered->label,
                ),
            );

            $this->assertNotSame(
                '',
                trim(
                    $registered->description,
                ),
            );
        }
    }

    public function test_luxe_newsletter_remains_default_template(): void
    {
        $this->assertSame(
            FooterTemplate::LuxeNewsletter,
            (
                new FooterTemplateRegistry
            )->default(),
        );
    }

    public function test_every_template_uses_same_stable_top_level_config_contract(): void
    {
        $defaults =
            new FooterConfigDefaults;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $config =
                $defaults->for(
                    $template,
                );

            $this->assertSame(
                self::CONFIG_KEYS,
                array_keys(
                    $config,
                ),
                sprintf(
                    'Footer template [%s] does not match the stable Footer Builder config contract.',
                    $template->value,
                ),
            );
        }
    }

    public function test_every_template_default_round_trips_through_schema(): void
    {
        $defaults =
            new FooterConfigDefaults;

        $schema =
            new FooterConfigSchema;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $config =
                $defaults->for(
                    $template,
                );

            $this->assertSame(
                $config,
                $schema->validate(
                    $template,
                    $config,
                ),
                sprintf(
                    'Default config for footer template [%s] failed schema round-trip.',
                    $template->value,
                ),
            );
        }
    }

    public function test_empty_historical_config_normalizes_to_template_defaults(): void
    {
        $defaults =
            new FooterConfigDefaults;

        $schema =
            new FooterConfigSchema;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $this->assertSame(
                $defaults->for(
                    $template,
                ),
                $schema->validate(
                    $template,
                    [],
                ),
                sprintf(
                    'Empty config did not normalize to defaults for footer template [%s].',
                    $template->value,
                ),
            );
        }
    }

    public function test_developer_credit_contract_is_shared_by_every_template(): void
    {
        $defaults =
            new FooterConfigDefaults;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $config =
                $defaults->for(
                    $template,
                );

            $developer =
                $config[
                    'developer'
                ];

            $this->assertIsArray(
                $developer,
            );

            $this->assertSame(
                [
                    'prefix',
                    'name',
                    'url',
                ],
                array_keys(
                    $developer,
                ),
            );

            $this->assertIsString(
                $developer[
                    'prefix'
                ],
            );

            $this->assertNotSame(
                '',
                trim(
                    $developer[
                        'prefix'
                    ],
                ),
            );

            $this->assertIsString(
                $developer[
                    'name'
                ],
            );

            $this->assertNotSame(
                '',
                trim(
                    $developer[
                        'name'
                    ],
                ),
            );
        }
    }

    public function test_developer_blinking_dot_is_not_optional_configuration(): void
    {
        $defaults =
            new FooterConfigDefaults;

        foreach (
            FooterTemplate::cases() as $template
        ) {
            $config =
                $defaults->for(
                    $template,
                );

            $developer =
                $config[
                    'developer'
                ];

            $this->assertIsArray(
                $developer,
            );

            $this->assertArrayNotHasKey(
                'show_dot',
                $developer,
            );

            $this->assertArrayNotHasKey(
                'show_developer_dot',
                $developer,
            );

            $this->assertArrayNotHasKey(
                'animation',
                $developer,
            );

            $this->assertArrayNotHasKey(
                'dot_animation',
                $developer,
            );
        }
    }

    public function test_luxe_newsletter_default_contract_is_stable(): void
    {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $this->assertTrue(
            $config[
                'newsletter'
            ][
                'enabled'
            ],
        );

        $this->assertCount(
            4,
            $config[
                'value_props'
            ],
        );

        $this->assertCount(
            4,
            $config[
                'payment_methods'
            ],
        );

        $this->assertFalse(
            $config[
                'localization'
            ][
                'enabled'
            ],
        );

        $this->assertFalse(
            $config[
                'promotion'
            ][
                'enabled'
            ],
        );
    }

    public function test_minimal_localized_default_contract_is_stable(): void
    {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MinimalLocalized,
            );

        $this->assertFalse(
            $config[
                'newsletter'
            ][
                'enabled'
            ],
        );

        $this->assertTrue(
            $config[
                'localization'
            ][
                'enabled'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'localization'
            ][
                'languages'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'localization'
            ][
                'currencies'
            ],
        );

        $this->assertFalse(
            $config[
                'promotion'
            ][
                'enabled'
            ],
        );
    }

    public function test_marketplace_trust_default_contract_is_stable(): void
    {
        $config =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::MarketplaceTrust,
            );

        $this->assertFalse(
            $config[
                'newsletter'
            ][
                'enabled'
            ],
        );

        $this->assertFalse(
            $config[
                'localization'
            ][
                'enabled'
            ],
        );

        $this->assertTrue(
            $config[
                'promotion'
            ][
                'enabled'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'popular_links'
            ],
        );

        $this->assertNotEmpty(
            $config[
                'certifications'
            ],
        );
    }

    public function test_unknown_top_level_configuration_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'custom_css'
        ] =
            'position:fixed';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'custom_css',
        );
    }

    public function test_unknown_nested_configuration_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'custom_html'
        ] =
            '<script>alert(1)</script>';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'developer.custom_html',
        );
    }

    public function test_unknown_brand_configuration_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'brand'
        ][
            'custom_class'
        ] =
            'fixed inset-0';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'brand.custom_class',
        );
    }

    public function test_unsafe_javascript_url_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            'javascript:alert(1)';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'developer.url',
        );
    }

    public function test_unsafe_data_url_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            'data:text/html;base64,PHNjcmlwdD4=';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'developer.url',
        );
    }

    public function test_protocol_relative_url_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            '//malicious.example';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'developer.url',
        );
    }

    public function test_internal_relative_urls_are_supported(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            '/about';

        $config[
            'link_groups'
        ][0][
            'links'
        ][0][
            'url'
        ] =
            '/products';

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                FooterTemplate::LuxeNewsletter,
                $config,
            );

        $this->assertSame(
            '/about',
            $validated[
                'developer'
            ][
                'url'
            ],
        );

        $this->assertSame(
            '/products',
            $validated[
                'link_groups'
            ][0][
                'links'
            ][0][
                'url'
            ],
        );
    }

    public function test_fragment_urls_are_supported(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            '#contact';

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                FooterTemplate::LuxeNewsletter,
                $config,
            );

        $this->assertSame(
            '#contact',
            $validated[
                'developer'
            ][
                'url'
            ],
        );
    }

    public function test_https_urls_are_supported(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'developer'
        ][
            'url'
        ] =
            'https://example.com';

        $validated =
            (
                new FooterConfigSchema
            )->validate(
                FooterTemplate::LuxeNewsletter,
                $config,
            );

        $this->assertSame(
            'https://example.com',
            $validated[
                'developer'
            ][
                'url'
            ],
        );
    }

    public function test_unsupported_social_platform_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'social_links'
        ][0][
            'platform'
        ] =
            'unknown-network';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'social_links.0.platform',
        );
    }

    public function test_unsupported_value_prop_icon_is_rejected(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'value_props'
        ][0][
            'icon'
        ] =
            'custom-svg';

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'value_props.0.icon',
        );
    }

    public function test_enabled_newsletter_requires_placeholder(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'newsletter'
        ][
            'placeholder'
        ] =
            null;

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'newsletter.placeholder',
        );
    }

    public function test_enabled_newsletter_requires_button_label(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::LuxeNewsletter,
            );

        $config[
            'newsletter'
        ][
            'button_label'
        ] =
            null;

        $this->assertInvalidConfig(
            template: FooterTemplate::LuxeNewsletter,
            config: $config,
            errorKey: 'newsletter.button_label',
        );
    }

    public function test_enabled_localization_requires_languages(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MinimalLocalized,
            );

        $config[
            'localization'
        ][
            'languages'
        ] =
            [];

        $this->assertInvalidConfig(
            template: FooterTemplate::MinimalLocalized,
            config: $config,
            errorKey: 'localization.languages',
        );
    }

    public function test_enabled_localization_requires_currencies(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MinimalLocalized,
            );

        $config[
            'localization'
        ][
            'currencies'
        ] =
            [];

        $this->assertInvalidConfig(
            template: FooterTemplate::MinimalLocalized,
            config: $config,
            errorKey: 'localization.currencies',
        );
    }

    public function test_localization_option_codes_are_restricted(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MinimalLocalized,
            );

        $config[
            'localization'
        ][
            'languages'
        ][0][
            'code'
        ] =
            '<script>';

        $this->assertInvalidConfig(
            template: FooterTemplate::MinimalLocalized,
            config: $config,
            errorKey: 'localization.languages.0.code',
        );
    }

    public function test_enabled_promotion_requires_message(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'promotion'
        ][
            'message'
        ] =
            null;

        $this->assertInvalidConfig(
            template: FooterTemplate::MarketplaceTrust,
            config: $config,
            errorKey: 'promotion.message',
        );
    }

    public function test_enabled_promotion_requires_button_label(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'promotion'
        ][
            'button_label'
        ] =
            null;

        $this->assertInvalidConfig(
            template: FooterTemplate::MarketplaceTrust,
            config: $config,
            errorKey: 'promotion.button_label',
        );
    }

    public function test_enabled_promotion_requires_safe_button_url(): void
    {
        $config =
            $this->defaults(
                FooterTemplate::MarketplaceTrust,
            );

        $config[
            'promotion'
        ][
            'button_url'
        ] =
            'javascript:alert(1)';

        $this->assertInvalidConfig(
            template: FooterTemplate::MarketplaceTrust,
            config: $config,
            errorKey: 'promotion.button_url',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(
        FooterTemplate $template,
    ): array {
        return (
            new FooterConfigDefaults
        )->for(
            $template,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertInvalidConfig(
        FooterTemplate $template,
        array $config,
        string $errorKey,
    ): void {
        try {
            (
                new FooterConfigSchema
            )->validate(
                $template,
                $config,
            );

            $this->fail(
                sprintf(
                    'Expected footer configuration field [%s] to be rejected.',
                    $errorKey,
                ),
            );
        } catch (
            InvalidFooterConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                $errorKey,
                $exception->errors(),
            );
        }
    }
}
