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
                ],
            ],
            $data->toArray(),
        );
    }
}
