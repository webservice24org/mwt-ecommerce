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

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $definitions[0],
        );

        $this->assertSame(
            'featured_products',
            $definitions[0]->type,
        );
    }
}
