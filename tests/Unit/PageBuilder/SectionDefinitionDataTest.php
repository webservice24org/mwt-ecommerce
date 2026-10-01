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
            $data['template_default_configs'],
        );

        $this->assertArrayHasKey(
            'image_slider',
            $data['template_default_configs'],
        );

        $this->assertArrayHasKey(
            'static',
            $data['template_default_configs'],
        );

        $this->assertSame(
            $definition->defaultConfigForTemplate(
                'content_slider',
            ),
            $data['template_default_configs']['content_slider'],
        );

        $this->assertSame(
            $definition->defaultConfigForTemplate(
                'image_slider',
            ),
            $data['template_default_configs']['image_slider'],
        );

        $this->assertSame(
            $definition->defaultConfigForTemplate(
                'static',
            ),
            $data['template_default_configs']['static'],
        );

        $this->assertSame(
            $data['template_default_configs']['content_slider'],
            $data['default_config'],
        );
    }
}
