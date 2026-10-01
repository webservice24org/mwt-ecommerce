<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\HeroConfigSchema;

final class HeroSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::Hero;
    }

    public function label(): string
    {
        return $this->type()->label();
    }

    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'content_slider',
                label: 'Content Slider',
                description: 'Create a sliding hero with background colors or images, text content, optional buttons, and transition effects.',
                category: 'Hero',
            ),

            new SectionTemplateData(
                key: 'image_slider',
                label: 'Image Slider',
                description: 'Create a clean sliding hero using banner images with optional links and transition effects.',
                category: 'Hero',
            ),

            new SectionTemplateData(
                key: 'static',
                label: 'Static Hero',
                description: 'Display a non-sliding hero with a background color or image, text content, and optional buttons.',
                category: 'Hero',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'content_slider';
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
            'content_slider' => $this->contentSliderDefaults(),
            'image_slider' => $this->imageSliderDefaults(),
            'static' => $this->staticDefaults(),

            default => throw new \InvalidArgumentException(
                \sprintf(
                    'Unsupported Hero template [%s].',
                    $template,
                ),
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function contentSliderDefaults(): array
    {
        return [
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'effect' => 'slide_left',
            'show_arrows' => true,
            'show_dots' => true,

            'slides' => [
                [
                    'background_color' => '#111827',
                    'background_image' => null,

                    'top_title' => 'New Collection',
                    'title' => 'Discover Something New',
                    'description' => 'Explore our latest products and collections.',

                    'alignment' => 'left',

                    'primary_button' => [
                        'label' => 'Shop Now',
                        'url' => '/products',
                    ],

                    'secondary_button' => null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageSliderDefaults(): array
    {
        return [
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'effect' => 'slide_left',
            'show_arrows' => true,
            'show_dots' => true,

            'slides' => [
                [
                    'image' => null,
                    'alt' => '',
                    'url' => '',
                    'alignment' => 'left',
                    'primary_button' => null,
                    'secondary_button' => null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staticDefaults(): array
    {
        return [
            'autoplay' => false,
            'autoplay_delay' => 5000,
            'effect' => 'fade',
            'show_arrows' => false,
            'show_dots' => false,

            'slides' => [
                [
                    'background_color' => '#111827',
                    'background_image' => null,

                    'top_title' => 'Welcome',
                    'title' => 'Discover Our Store',
                    'description' => 'Explore our products and collections.',

                    'alignment' => 'left',

                    'primary_button' => [
                        'label' => 'Shop Now',
                        'url' => '/products',
                    ],

                    'secondary_button' => null,
                ],
            ],
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new HeroConfigSchema;
    }
}
