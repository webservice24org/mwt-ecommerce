<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class GetSectionDefinitionsQueryTest extends TestCase
{
    public function test_query_returns_only_registered_section_definitions(): void
    {
        $query = new GetSectionDefinitionsQuery(
            new SectionRegistry,
        );

        $definitions = $query->handle();

        $this->assertCount(1, $definitions);

        $definition = $definitions[0];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $definition,
        );

        $this->assertSame(
            'featured_products',
            $definition->type,
        );

        $this->assertSame(
            'Featured Products',
            $definition->label,
        );

        $this->assertCount(
            1,
            $definition->templates,
        );

        $template = $definition->templates[0];

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $definition->defaultConfig,
        );

        $this->assertSame(
            'Product Grid',
            $template->label,
        );

        $this->assertSame(
            'Display a curated selection of featured products in a responsive grid.',
            $template->description,
        );

        $this->assertSame(
            'Products',
            $template->category,
        );
    }
}
