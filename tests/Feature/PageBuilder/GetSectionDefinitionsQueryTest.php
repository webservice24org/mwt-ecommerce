<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use Tests\TestCase;

final class GetSectionDefinitionsQueryTest extends TestCase
{
    public function test_it_returns_registered_section_definitions(): void
    {
        $definitions = app(
            GetSectionDefinitionsQuery::class,
        )->handle();

        $this->assertCount(
            1,
            $definitions,
        );

        $definition = $definitions[0];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $definition,
        );

        $this->assertSame(
            SectionType::FeaturedProducts->value,
            $definition->type,
        );

        $this->assertSame(
            'Featured Products',
            $definition->label,
        );

        $this->assertSame(
            'grid',
            $definition->defaultTemplate,
        );

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

        $this->assertCount(
            1,
            $definition->templates,
        );

        $template = $definition->templates[0];

        $this->assertSame(
            'grid',
            $template->key,
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
