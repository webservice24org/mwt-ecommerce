<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\SpacerDividerSection;
use PHPUnit\Framework\TestCase;

final class SpacerDividerFinalRegressionTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const TEMPLATES = [
        'responsive_spacer',
        'line_divider',
        'label_divider',
        'gradient_divider',
    ];

    public function test_spacer_divider_identity_is_stable(): void
    {
        $definition =
            new SpacerDividerSection;

        $this->assertSame(
            SectionType::SpacerDivider,
            $definition->type(),
        );

        $this->assertSame(
            'spacer_divider',
            SectionType::SpacerDivider->value,
        );

        $this->assertSame(
            'Spacer / Divider',
            SectionType::SpacerDivider->label(),
        );

        $this->assertSame(
            'Spacer / Divider',
            $definition->label(),
        );
    }

    public function test_spacer_divider_is_registered(): void
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

    public function test_template_contract_is_stable(): void
    {
        $templates =
            (
                new SpacerDividerSection
            )->templates();

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

    public function test_registry_supports_every_spacer_divider_template(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            self::TEMPLATES as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::SpacerDivider,
                    $template,
                ),
            );
        }

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::SpacerDivider,
                'unknown',
            ),
        );
    }

    public function test_responsive_spacer_remains_the_default_template(): void
    {
        $definition =
            new SpacerDividerSection;

        $this->assertSame(
            'responsive_spacer',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            $definition->defaultConfigForTemplate(
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
            self::TEMPLATES as $template
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

    public function test_every_template_keeps_same_configuration_contract(): void
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
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertSame(
                $expectedKeys,
                array_keys(
                    $config,
                ),
            );
        }
    }

    public function test_template_default_config_map_is_stable(): void
    {
        $definition =
            new SpacerDividerSection;

        $this->assertSame(
            self::TEMPLATES,
            array_keys(
                $definition
                    ->templateDefaultConfigs(),
            ),
        );
    }

    public function test_responsive_spacer_keeps_adaptive_default_heights(): void
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

    public function test_every_template_keeps_valid_height_defaults(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            foreach (
                [
                    'mobile_height',
                    'tablet_height',
                    'desktop_height',
                ] as $key
            ) {
                $this->assertIsInt(
                    $config[
                        $key
                    ],
                );

                $this->assertGreaterThanOrEqual(
                    0,
                    $config[
                        $key
                    ],
                );

                $this->assertLessThanOrEqual(
                    320,
                    $config[
                        $key
                    ],
                );
            }
        }
    }

    public function test_every_template_keeps_valid_line_configuration(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertTrue(
                in_array(
                    $config[
                        'line_style'
                    ],
                    [
                        'solid',
                        'dashed',
                        'dotted',
                    ],
                    true,
                ),
            );

            $this->assertIsInt(
                $config[
                    'line_thickness'
                ],
            );

            $this->assertGreaterThanOrEqual(
                1,
                $config[
                    'line_thickness'
                ],
            );

            $this->assertLessThanOrEqual(
                4,
                $config[
                    'line_thickness'
                ],
            );

            $this->assertMatchesRegularExpression(
                '/^#[0-9a-f]{6}$/',
                $config[
                    'line_color'
                ],
            );
        }
    }

    public function test_every_template_keeps_valid_layout_configuration(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertTrue(
                in_array(
                    $config[
                        'width'
                    ],
                    [
                        'full',
                        'three_quarter',
                        'half',
                    ],
                    true,
                ),
            );

            $this->assertTrue(
                in_array(
                    $config[
                        'alignment'
                    ],
                    [
                        'left',
                        'center',
                        'right',
                    ],
                    true,
                ),
            );
        }
    }

    public function test_every_template_keeps_valid_label_configuration(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertTrue(
                $config['label'] === null ||
                is_string(
                    $config['label'],
                ),
            );

            if (
                is_string(
                    $config['label'],
                )
            ) {
                $this->assertLessThanOrEqual(
                    120,
                    mb_strlen(
                        $config['label'],
                    ),
                );
            }

            $this->assertTrue(
                in_array(
                    $config[
                        'label_style'
                    ],
                    [
                        'plain',
                        'pill',
                    ],
                    true,
                ),
            );

            $this->assertMatchesRegularExpression(
                '/^#[0-9a-f]{6}$/',
                $config[
                    'text_color'
                ],
            );
        }
    }

    public function test_every_template_keeps_valid_gradient_configuration(): void
    {
        $definition =
            new SpacerDividerSection;

        foreach (
            self::TEMPLATES as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            foreach (
                [
                    'gradient_from',
                    'gradient_via',
                    'gradient_to',
                ] as $key
            ) {
                $this->assertMatchesRegularExpression(
                    '/^#[0-9a-f]{6}$/',
                    $config[
                        $key
                    ],
                );
            }
        }
    }
}
