<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\BrandSourceType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\BrandConfigSchema;
use InvalidArgumentException;

final class BrandSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::Brands;
    }

    public function label(): string
    {
        return SectionType::Brands->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'logo_strip',
                label: 'Minimal Logo Strip',
                description: 'A clean classic e-commerce strip of brand logos with subtle grayscale styling.',
                category: 'Brands',
            ),

            new SectionTemplateData(
                key: 'brand_cards',
                label: 'Brand Cards',
                description: 'Rich brand cards with logo or name, description, product count, and an explore action.',
                category: 'Brands',
            ),

            new SectionTemplateData(
                key: 'logo_marquee',
                label: 'Infinite Logo Carousel',
                description: 'A continuously moving brand-logo marquee with side fades and pause-on-hover behavior.',
                category: 'Brands',
            ),

            new SectionTemplateData(
                key: 'spotlight_banner',
                label: 'Brand Spotlight Banner',
                description: 'A bold promotional brand showcase with supporting copy, actions, and featured brand logos.',
                category: 'Brands',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'logo_strip';
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
            'eyebrow' => null,
            'heading' => 'Shop by Brand',
            'description' => '',

            'source' => [
                'type' => BrandSourceType::All->value,
            ],

            'limit' => 12,
            'columns' => 4,
            'alignment' => 'center',

            'background_color' => '#ffffff',
            'text_theme' => 'dark',

            'show_name' => false,
            'show_description' => false,
            'show_product_count' => false,

            'view_all_label' => null,
            'view_all_url' => null,

            'primary_button_label' => null,
            'primary_button_url' => null,

            'secondary_button_label' => null,
            'secondary_button_url' => null,

            'marquee_duration' => 25,
            'pause_on_hover' => true,
        ];

        return match ($template) {
            'logo_strip' => array_replace(
                $common,
                [
                    'eyebrow' => null,
                    'heading' => 'Trusted by Leading Brands',
                    'description' => '',
                    'limit' => 12,
                    'columns' => 6,
                    'alignment' => 'center',
                    'show_name' => false,
                    'show_description' => false,
                    'show_product_count' => false,
                ],
            ),

            'brand_cards' => array_replace(
                $common,
                [
                    'eyebrow' => 'Official Partners',
                    'heading' => 'Shop Top Featured Brands',
                    'description' => '',
                    'limit' => 8,
                    'columns' => 4,
                    'alignment' => 'left',
                    'show_name' => true,
                    'show_description' => true,
                    'show_product_count' => true,
                ],
            ),

            'logo_marquee' => array_replace(
                $common,
                [
                    'eyebrow' => 'Our Retail Network',
                    'heading' => 'Featured in Global Stores',
                    'description' => '',
                    'limit' => 16,
                    'columns' => 6,
                    'alignment' => 'center',
                    'show_name' => false,
                    'show_description' => false,
                    'show_product_count' => false,
                    'marquee_duration' => 25,
                    'pause_on_hover' => true,
                ],
            ),

            'spotlight_banner' => array_replace(
                $common,
                [
                    'eyebrow' => 'Brand Spotlight',
                    'heading' => 'Partnered with Leading Global Brands',
                    'description' => 'Discover authentic products from trusted brands and verified suppliers.',
                    'limit' => 4,
                    'columns' => 2,
                    'alignment' => 'left',
                    'background_color' => '#312e81',
                    'text_theme' => 'light',
                    'show_name' => true,
                    'show_description' => false,
                    'show_product_count' => false,
                ],
            ),

            default => throw new InvalidArgumentException(
                "Unsupported brand template [{$template}].",
            ),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'logo_strip' => $this->defaultConfigForTemplate(
                'logo_strip',
            ),

            'brand_cards' => $this->defaultConfigForTemplate(
                'brand_cards',
            ),

            'logo_marquee' => $this->defaultConfigForTemplate(
                'logo_marquee',
            ),

            'spotlight_banner' => $this->defaultConfigForTemplate(
                'spotlight_banner',
            ),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new BrandConfigSchema;
    }
}
