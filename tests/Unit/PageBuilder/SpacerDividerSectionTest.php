<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\SpacerDividerSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SpacerDividerSectionTest extends TestCase
{
    public function test_spacer_divider_section_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertTrue(
            $registry->has(
                SectionType::SpacerDivider,
            ),
        );

        $this->assertInstanceOf(
            SpacerDividerSection::class,
            $registry->get(
                SectionType::SpacerDivider,
            ),
        );
    }

    public function test_spacer_divider_exposes_four_templates(): void
    {
        $definition =
            new SpacerDividerSection;

        $templates =
            $definition->templates();

        $this->assertCount(
            4,
            $templates,
        );

        foreach (
            $templates as $template
        ) {
            $this->assertInstanceOf(
                SectionTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'responsive_spacer',
                'line_divider',
                'label_divider',
                'gradient_divider',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->key,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'Responsive Spacer',
                'Line Divider',
                'Label Divider',
                'Gradient Divider',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->label,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'Layout',
                'Layout',
                'Layout',
                'Layout',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_registry_supports_all_spacer_divider_templates(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'responsive_spacer',
                'line_divider',
                'label_divider',
                'gradient_divider',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::SpacerDivider,
                    $template,
                ),
            );
        }
    }

    public function test_responsive_spacer_is_default_template(): void
    {
        $definition =
            new SpacerDividerSection;

        $this->assertSame(
            'responsive_spacer',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'responsive_spacer',
                ),
            $definition->defaultConfig(),
        );
    }

    public function test_every_template_default_passes_schema(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            [
                'responsive_spacer',
                'line_divider',
                'label_divider',
                'gradient_divider',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertSame(
                $config,
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    ),
            );
        }
    }

    public function test_all_templates_share_stable_config_contract(): void
    {
        $definition =
            new SpacerDividerSection;

        $expectedKeys = [
            'mobile_height',
            'tablet_height',
            'desktop_height',
            'line_style',
            'line_color',
            'line_thickness',
            'width',
            'alignment',
            'label',
            'label_style',
            'text_color',
            'gradient_from',
            'gradient_via',
            'gradient_to',
        ];

        foreach (
            $definition->templateDefaultConfigs() as $config
        ) {
            $this->assertSame(
                $expectedKeys,
                array_keys(
                    $config,
                ),
            );
        }
    }

    public function test_responsive_spacer_uses_recommended_adaptive_defaults(): void
    {
        $config =
            (
                new SpacerDividerSection
            )->defaultConfigForTemplate(
                'responsive_spacer',
            );

        $this->assertSame(
            32,
            $config[
                'mobile_height'
            ],
        );

        $this->assertSame(
            64,
            $config[
                'tablet_height'
            ],
        );

        $this->assertSame(
            96,
            $config[
                'desktop_height'
            ],
        );
    }

    public function test_unknown_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new SpacerDividerSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
