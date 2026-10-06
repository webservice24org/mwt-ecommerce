<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\BrandSection;
use PHPUnit\Framework\TestCase;

final class BrandSectionRegressionTest extends TestCase
{
    public function test_brand_section_is_registered_with_all_four_templates(): void
    {
        $registry =
            new SectionRegistry;

        $definition =
            $registry->get(
                SectionType::Brands,
            );

        $this->assertInstanceOf(
            BrandSection::class,
            $definition,
        );

        $this->assertSame(
            SectionType::Brands,
            $definition->type(),
        );

        $this->assertSame(
            'Brands',
            $definition->label(),
        );

        $this->assertSame(
            'logo_strip',
            $definition->defaultTemplate(),
        );

        $templateKeys =
            array_map(
                static fn (
                    $template,
                ): string => $template->key,
                $definition->templates(),
            );

        $this->assertSame(
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ],
            $templateKeys,
        );
    }

    public function test_registry_supports_every_brand_template(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::Brands,
                    $template,
                ),
                "Expected Brand template [{$template}] to be registered.",
            );
        }

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::Brands,
                'unknown-brand-template',
            ),
        );
    }

    public function test_every_brand_template_default_configuration_is_valid(): void
    {
        $definition =
            new BrandSection;

        foreach (
            $definition->templates() as $template
        ) {
            $config =
                $definition->defaultConfigForTemplate(
                    $template->key,
                );

            $validated =
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    );

            $this->assertSame(
                $config,
                $validated,
                "Default configuration for Brand template [{$template->key}] must pass its schema unchanged.",
            );
        }
    }

    public function test_brand_template_defaults_preserve_expected_source_contract(): void
    {
        $definition =
            new BrandSection;

        foreach (
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ] as $template
        ) {
            $config =
                $definition->defaultConfigForTemplate(
                    $template,
                );

            $this->assertArrayHasKey(
                'source',
                $config,
            );

            $this->assertSame(
                [
                    'type' => 'all',
                ],
                $config['source'],
            );

            $this->assertArrayHasKey(
                'limit',
                $config,
            );

            $this->assertIsInt(
                $config['limit'],
            );

            $this->assertGreaterThanOrEqual(
                1,
                $config['limit'],
            );

            $this->assertLessThanOrEqual(
                24,
                $config['limit'],
            );
        }
    }

    public function test_brand_template_defaults_keep_security_sensitive_fields_safe(): void
    {
        $definition =
            new BrandSection;

        foreach (
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ] as $template
        ) {
            $config =
                $definition->defaultConfigForTemplate(
                    $template,
                );

            $this->assertMatchesRegularExpression(
                '/^#[0-9a-f]{6}$/',
                $config['background_color'],
            );

            $this->assertContains(
                $config['text_theme'],
                [
                    'light',
                    'dark',
                ],
                true,
            );

            $this->assertContains(
                $config['alignment'],
                [
                    'left',
                    'center',
                ],
                true,
            );

            $this->assertGreaterThanOrEqual(
                10,
                $config['marquee_duration'],
            );

            $this->assertLessThanOrEqual(
                60,
                $config['marquee_duration'],
            );

            $this->assertIsBool(
                $config['pause_on_hover'],
            );
        }
    }
}
