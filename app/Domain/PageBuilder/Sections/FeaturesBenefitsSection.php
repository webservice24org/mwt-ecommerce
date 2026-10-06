<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\FeaturesBenefitsConfigSchema;
use InvalidArgumentException;

final class FeaturesBenefitsSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::FeaturesBenefits;
    }

    public function label(): string
    {
        return SectionType::FeaturesBenefits->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'icon_grid',
                label: 'Icon Grid',
                description: 'Display benefits in a responsive grid with an icon, title, and supporting text.',
                category: 'Content',
            ),

            new SectionTemplateData(
                key: 'image_grid',
                label: 'Image Grid',
                description: 'Display benefits as visual cards with an image, title, and supporting text.',
                category: 'Content',
            ),

            new SectionTemplateData(
                key: 'horizontal_benefits',
                label: 'Horizontal Benefits',
                description: 'Display compact benefits horizontally for trust, service, and store highlights.',
                category: 'Content',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'icon_grid';
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
        $common = [
            'eyebrow' => 'Why choose us',

            'heading' => 'Benefits built around your shopping experience',

            'description' => 'Everything you need for a simple, secure, and dependable shopping experience.',

            /*
        * Keep items in the canonical config
        * position. Each template replaces this
        * value without changing array key order.
        */
            'items' => [],

            'columns' => 3,

            'alignment' => 'center',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',
        ];

        return match ($template) {
            'icon_grid' => array_replace(
                $common,
                [
                    'items' => $this->defaultItems(
                        withIcons: true,
                    ),
                ],
            ),

            'image_grid' => array_replace(
                $common,
                [
                    'heading' => 'What makes us different',
                    'description' => 'Explore the benefits and services that make shopping with us easier.',
                    'alignment' => 'left',
                    'items' => $this->defaultItems(
                        withIcons: false,
                    ),
                ],
            ),

            'horizontal_benefits' => array_replace(
                $common,
                [
                    'eyebrow' => null,
                    'heading' => 'Shopping made simple',
                    'description' => '',
                    'alignment' => 'left',
                    'items' => $this->defaultItems(
                        withIcons: true,
                    ),
                ],
            ),

            default => throw new InvalidArgumentException(
                "Unsupported features / benefits template [{$template}].",
            ),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'icon_grid' => $this->defaultConfigForTemplate(
                'icon_grid',
            ),

            'image_grid' => $this->defaultConfigForTemplate(
                'image_grid',
            ),

            'horizontal_benefits' => $this->defaultConfigForTemplate(
                'horizontal_benefits',
            ),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new FeaturesBenefitsConfigSchema;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultItems(
        bool $withIcons,
    ): array {
        return [
            [
                'title' => 'Fast Delivery',

                'description' => 'Reliable delivery designed to get your order to you quickly.',

                'icon' => $withIcons
                        ? 'truck'
                        : null,

                'image' => null,

                'image_alt' => null,

                'link_label' => null,

                'link_url' => null,
            ],

            [
                'title' => 'Secure Payments',

                'description' => 'Shop confidently with a secure and dependable checkout experience.',

                'icon' => $withIcons
                        ? 'shield-check'
                        : null,

                'image' => null,

                'image_alt' => null,

                'link_label' => null,

                'link_url' => null,
            ],

            [
                'title' => 'Helpful Support',

                'description' => 'Get assistance when you need help with products, orders, or delivery.',

                'icon' => $withIcons
                        ? 'headphones'
                        : null,

                'image' => null,

                'image_alt' => null,

                'link_label' => null,

                'link_url' => null,
            ],
        ];
    }
}
