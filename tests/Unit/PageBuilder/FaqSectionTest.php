<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\FaqSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FaqSectionTest extends TestCase
{
    public function test_faq_section_is_registered(): void
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

    public function test_faq_exposes_three_templates(): void
    {
        $definition =
            new FaqSection;

        $templates =
            $definition->templates();

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

    public function test_registry_supports_all_faq_templates(): void
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
    }

    public function test_accordion_is_the_default_template(): void
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

    public function test_every_faq_template_default_is_valid(): void
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

    public function test_faq_defaults_contain_expected_item_contract(): void
    {
        $definition =
            new FaqSection;

        foreach (
            $definition->templateDefaultConfigs() as $config
        ) {
            $this->assertNotEmpty(
                $config[
                    'items'
                ],
            );

            foreach (
                $config[
                    'items'
                ] as $item
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
            }
        }
    }

    public function test_faq_defaults_include_interaction_contract(): void
    {
        $definition =
            new FaqSection;

        foreach (
            $definition->templateDefaultConfigs() as $config
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

    public function test_unknown_faq_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new FaqSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
