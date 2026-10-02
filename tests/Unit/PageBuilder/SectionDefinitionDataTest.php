<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class SectionDefinitionDataTest extends TestCase
{
    public function test_definition_serializes_to_builder_safe_contract(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $data = SectionDefinitionData::fromDefinition(
            $definition,
        );

        $this->assertSame(
            [
                'type' => 'featured_products',
                'label' => 'Featured Products',
                'templates' => [
                    [
                        'key' => 'grid',
                        'label' => 'Product Grid',
                        'description' => 'Display a curated selection of featured products in a responsive grid.',
                        'category' => 'Products',
                    ],
                ],
                'default_template' => 'grid',
                'default_config' => [
                    'title' => 'Featured Products',
                    'limit' => 8,
                    'source' => [
                        'type' => 'featured',
                    ],
                ],
                'template_default_configs' => [
                    'grid' => [
                        'title' => 'Featured Products',
                        'limit' => 8,
                        'source' => [
                            'type' => 'featured',
                        ],
                    ],
                ],
            ],
            $data->toArray(),
        );
    }

    public function test_hero_definition_exposes_defaults_for_every_template(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $data = SectionDefinitionData::fromDefinition(
            $definition,
        )->toArray();

        $this->assertSame(
            'content_slider',
            $data['default_template'],
        );

        $this->assertArrayHasKey(
            'content_slider',
            $data[
                'template_default_configs'
            ],
        );

        $this->assertArrayHasKey(
            'image_slider',
            $data[
                'template_default_configs'
            ],
        );

        $this->assertArrayHasKey(
            'static',
            $data[
                'template_default_configs'
            ],
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'content_slider',
                ),
            $data[
                'template_default_configs'
            ]['content_slider'],
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'image_slider',
                ),
            $data[
                'template_default_configs'
            ]['image_slider'],
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'static',
                ),
            $data[
                'template_default_configs'
            ]['static'],
        );

        $this->assertSame(
            $data[
                'template_default_configs'
            ]['content_slider'],
            $data['default_config'],
        );
    }

    public function test_product_categories_definition_serializes_to_builder_safe_contract(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $data = SectionDefinitionData::fromDefinition(
            $definition,
        )->toArray();

        $gridConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
        ];

        $cardsConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 3,
            'show_product_count' => true,
        ];

        $carouselConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ];

        $this->assertSame(
            'product_categories',
            $data['type'],
        );

        $this->assertSame(
            'Product Categories',
            $data['label'],
        );

        $this->assertSame(
            [
                [
                    'key' => 'grid',
                    'label' => 'Category Grid',
                    'description' => 'Display selected product categories in a responsive grid.',
                    'category' => 'Categories',
                ],
                [
                    'key' => 'cards',
                    'label' => 'Category Cards',
                    'description' => 'Display selected product categories as visual category cards.',
                    'category' => 'Categories',
                ],
                [
                    'key' => 'carousel',
                    'label' => 'Category Carousel',
                    'description' => 'Display selected product categories in a horizontal carousel.',
                    'category' => 'Categories',
                ],
            ],
            $data['templates'],
        );

        $this->assertSame(
            'grid',
            $data['default_template'],
        );

        $this->assertSame(
            $gridConfig,
            $data['default_config'],
        );

        $this->assertSame(
            [
                'grid' => $gridConfig,
                'cards' => $cardsConfig,
                'carousel' => $carouselConfig,
            ],
            $data[
                'template_default_configs'
            ],
        );
    }

    public function test_product_categories_definition_exposes_defaults_for_every_template(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $data = SectionDefinitionData::fromDefinition(
            $definition,
        )->toArray();

        foreach (
            [
                'grid',
                'cards',
                'carousel',
            ] as $template
        ) {
            $this->assertArrayHasKey(
                $template,
                $data[
                    'template_default_configs'
                ],
            );

            $this->assertSame(
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    ),
                $data[
                    'template_default_configs'
                ][$template],
            );
        }

        $this->assertSame(
            $data[
                'template_default_configs'
            ]['grid'],
            $data['default_config'],
        );
    }
}
