<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class ContentDefinitionQueryTest extends TestCase
{
    public function test_content_definition_is_exposed_to_the_builder(): void
    {
        $definitions = (
            new GetSectionDefinitionsQuery(
                new SectionRegistry,
            )
        )->handle();

        $this->assertCount(
            11,
            $definitions,
        );

        $content =
            $definitions[5];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $content,
        );

        $this->assertSame(
            SectionType::Content->value,
            $content->type,
        );

        $this->assertSame(
            'Content',
            $content->label,
        );

        $this->assertSame(
            'text',
            $content->defaultTemplate,
        );

        $textConfig = [
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
            $textConfig,
            $content->defaultConfig,
        );

        $this->assertCount(
            4,
            $content->templates,
        );

        $this->assertSame(
            [
                'text',
                'image_text',
                'text_image',
                'centered_content',
            ],
            array_column(
                $content->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'Text',
                'Image + Text',
                'Text + Image',
                'Centered Content',
            ],
            array_column(
                $content->templates,
                'label',
            ),
        );

        $this->assertSame(
            [
                'Content',
                'Content',
                'Content',
                'Content',
            ],
            array_column(
                $content->templates,
                'category',
            ),
        );

        $this->assertCount(
            4,
            $content->templateDefaultConfigs,
        );

        $this->assertSame(
            $textConfig,
            $content->templateDefaultConfigs[
                'text'
            ],
        );

        $this->assertSame(
            $textConfig,
            $content->templateDefaultConfigs[
                'image_text'
            ],
        );

        $this->assertSame(
            $textConfig,
            $content->templateDefaultConfigs[
                'text_image'
            ],
        );

        $this->assertSame(
            $centeredContentConfig,
            $content->templateDefaultConfigs[
                'centered_content'
            ],
        );
    }
}
