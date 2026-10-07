<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\FaqSection;
use PHPUnit\Framework\TestCase;

final class FaqFinalRegressionTest extends TestCase
{
    public function test_faq_section_identity_is_stable(): void
    {
        $definition =
            new FaqSection;

        $this->assertSame(
            SectionType::Faq,
            $definition->type(),
        );

        $this->assertSame(
            'faq',
            $definition->type()->value,
        );

        $this->assertSame(
            'FAQ',
            $definition->label(),
        );

        $this->assertSame(
            'FAQ',
            SectionType::Faq->label(),
        );
    }

    public function test_faq_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertTrue(
            $registry->has(
                SectionType::Faq,
            ),
        );

        $this->assertInstanceOf(
            FaqSection::class,
            $registry->get(
                SectionType::Faq,
            ),
        );
    }

    public function test_faq_template_contract_is_stable(): void
    {
        $templates =
            (
                new FaqSection
            )->templates();

        $this->assertCount(
            3,
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
                'accordion',
                'two_column',
                'side_panel',
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
                'Classic Accordion',
                'Two-Column FAQ',
                'Side Panel FAQ',
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
                'FAQ',
                'FAQ',
                'FAQ',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_registry_supports_every_faq_template(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'accordion',
                'two_column',
                'side_panel',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::Faq,
                    $template,
                ),
            );
        }

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::Faq,
                'unknown',
            ),
        );
    }

    public function test_accordion_remains_the_default_template(): void
    {
        $definition =
            new FaqSection;

        $this->assertSame(
            'accordion',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'accordion',
                ),
            $definition->defaultConfig(),
        );
    }

    public function test_every_faq_template_default_passes_the_schema(): void
    {
        $definition =
            new FaqSection;

        foreach (
            [
                'accordion',
                'two_column',
                'side_panel',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
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
            );
        }
    }

    public function test_every_template_keeps_the_same_configuration_contract(): void
    {
        $definition =
            new FaqSection;

        $expectedKeys = [
            'eyebrow',
            'heading',
            'description',
            'items',
            'alignment',
            'background_color',
            'text_theme',
            'open_first',
            'allow_multiple_open',
        ];

        foreach (
            [
                'accordion',
                'two_column',
                'side_panel',
            ] as $template
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

    public function test_every_default_faq_item_keeps_the_plain_text_contract(): void
    {
        $definition =
            new FaqSection;

        foreach (
            $definition
                ->templateDefaultConfigs() as $config
        ) {
            $items =
                $config[
                    'items'
                ];

            $this->assertIsArray(
                $items,
            );

            $this->assertNotEmpty(
                $items,
            );

            $this->assertLessThanOrEqual(
                16,
                count(
                    $items,
                ),
            );

            foreach (
                $items as $item
            ) {
                $this->assertSame(
                    [
                        'question',
                        'answer',
                    ],
                    array_keys(
                        $item,
                    ),
                );

                $this->assertIsString(
                    $item[
                        'question'
                    ],
                );

                $this->assertIsString(
                    $item[
                        'answer'
                    ],
                );

                $this->assertNotSame(
                    '',
                    trim(
                        $item[
                            'question'
                        ],
                    ),
                );

                $this->assertNotSame(
                    '',
                    trim(
                        $item[
                            'answer'
                        ],
                    ),
                );
            }
        }
    }

    public function test_every_template_keeps_valid_presentation_configuration(): void
    {
        $definition =
            new FaqSection;

        foreach (
            $definition
                ->templateDefaultConfigs() as $config
        ) {
            $this->assertTrue(
                in_array(
                    $config[
                        'alignment'
                    ],
                    [
                        'left',
                        'center',
                    ],
                    true,
                ),
            );

            $this->assertTrue(
                in_array(
                    $config[
                        'text_theme'
                    ],
                    [
                        'light',
                        'dark',
                    ],
                    true,
                ),
            );

            $this->assertMatchesRegularExpression(
                '/^#[0-9a-f]{6}$/',
                $config[
                    'background_color'
                ],
            );
        }
    }

    public function test_every_template_keeps_boolean_interaction_configuration(): void
    {
        $definition =
            new FaqSection;

        foreach (
            $definition
                ->templateDefaultConfigs() as $config
        ) {
            $this->assertIsBool(
                $config[
                    'open_first'
                ],
            );

            $this->assertIsBool(
                $config[
                    'allow_multiple_open'
                ],
            );
        }
    }
}
