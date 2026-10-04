<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\ContentConfigSchema;
use InvalidArgumentException;

final class ContentSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::Content;
    }

    public function label(): string
    {
        return SectionType::Content->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'text',
                label: 'Text',
                description: 'Display a heading and plain-text content in a simple layout.',
                category: 'Content',
            ),

            new SectionTemplateData(
                key: 'image_text',
                label: 'Image + Text',
                description: 'Display an image beside a heading and plain-text content, with the image first.',
                category: 'Content',
            ),

            new SectionTemplateData(
                key: 'text_image',
                label: 'Text + Image',
                description: 'Display a heading and plain-text content beside an image, with the text first.',
                category: 'Content',
            ),

            new SectionTemplateData(
                key: 'centered_content',
                label: 'Centered Content',
                description: 'Display a centered heading and plain-text content with an optional image.',
                category: 'Content',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'text';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return $this->defaultConfigForTemplate(
            $this->defaultTemplate(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForTemplate(
        string $template,
    ): array {
        return match ($template) {
            'text' => [
                'heading' => 'Content',
                'body' => 'Add your content here.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],

            'image_text' => [
                'heading' => 'Content',
                'body' => 'Add your content here.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],

            'text_image' => [
                'heading' => 'Content',
                'body' => 'Add your content here.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],

            'centered_content' => [
                'heading' => 'Content',
                'body' => 'Add your content here.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'center',
            ],

            default => throw new InvalidArgumentException(
                "Unsupported content template [{$template}].",
            ),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'text' => $this->defaultConfigForTemplate(
                'text',
            ),

            'image_text' => $this->defaultConfigForTemplate(
                'image_text',
            ),

            'text_image' => $this->defaultConfigForTemplate(
                'text_image',
            ),

            'centered_content' => $this->defaultConfigForTemplate(
                'centered_content',
            ),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new ContentConfigSchema;
    }
}
