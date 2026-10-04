<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\ContentSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContentSectionTest extends TestCase
{
    public function test_content_section_is_registered(): void
    {
        $registry = new SectionRegistry;

        $this->assertCount(
            6,
            $registry->all(),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::Content,
            ),
        );

        $this->assertInstanceOf(
            ContentSection::class,
            $registry->get(
                SectionType::Content,
            ),
        );
    }

    public function test_content_section_exposes_four_typed_templates(): void
    {
        $definition = (
            new SectionRegistry
        )->get(
            SectionType::Content,
        );

        $templates =
            $definition->templates();

        $this->assertCount(
            4,
            $templates,
        );

        foreach ($templates as $template) {
            $this->assertInstanceOf(
                SectionTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'text',
                'image_text',
                'text_image',
                'centered_content',
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
                'Text',
                'Image + Text',
                'Text + Image',
                'Centered Content',
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
                'Content',
                'Content',
                'Content',
                'Content',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_content_section_supports_all_registered_templates(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'text',
                'image_text',
                'text_image',
                'centered_content',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::Content,
                    $template,
                ),
            );
        }
    }

    public function test_content_section_has_template_specific_safe_defaults(): void
    {
        $definition = (
            new SectionRegistry
        )->get(
            SectionType::Content,
        );

        $textConfig = [
            'heading' => 'Content',
            'body' => 'Add your content here.',
            'image' => null,
            'image_alt' => null,
            'alignment' => 'left',
        ];

        $imageTextConfig = [
            'heading' => 'Content',
            'body' => 'Add your content here.',
            'image' => null,
            'image_alt' => null,
            'alignment' => 'left',
        ];

        $textImageConfig = [
            'heading' => 'Content',
            'body' => 'Add your content here.',
            'image' => null,
            'image_alt' => null,
            'alignment' => 'left',
        ];

        $centeredContentConfig = [
            'heading' => 'Content',
            'body' => 'Add your content here.',
            'image' => null,
            'image_alt' => null,
            'alignment' => 'center',
        ];

        $this->assertSame(
            'text',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            $textConfig,
            $definition->defaultConfig(),
        );

        $this->assertSame(
            $textConfig,
            $definition->defaultConfigForTemplate(
                'text',
            ),
        );

        $this->assertSame(
            $imageTextConfig,
            $definition->defaultConfigForTemplate(
                'image_text',
            ),
        );

        $this->assertSame(
            $textImageConfig,
            $definition->defaultConfigForTemplate(
                'text_image',
            ),
        );

        $this->assertSame(
            $centeredContentConfig,
            $definition->defaultConfigForTemplate(
                'centered_content',
            ),
        );
    }

    public function test_every_content_template_default_is_valid(): void
    {
        $definition = (
            new SectionRegistry
        )->get(
            SectionType::Content,
        );

        foreach (
            [
                'text',
                'image_text',
                'text_image',
                'centered_content',
            ] as $template
        ) {
            $config =
                $definition->defaultConfigForTemplate(
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

    public function test_content_section_exposes_default_config_for_every_template(): void
    {
        $definition =
            new ContentSection;

        $this->assertSame(
            [
                'text' => $definition
                    ->defaultConfigForTemplate(
                        'text',
                    ),

                'image_text' => $definition
                    ->defaultConfigForTemplate(
                        'image_text',
                    ),

                'text_image' => $definition
                    ->defaultConfigForTemplate(
                        'text_image',
                    ),

                'centered_content' => $definition
                    ->defaultConfigForTemplate(
                        'centered_content',
                    ),
            ],
            $definition
                ->templateDefaultConfigs(),
        );
    }

    public function test_content_section_rejects_unknown_default_template(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new ContentSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
