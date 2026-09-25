<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Queries\GetCatalogSourceDefinitionsQuery;
use App\Domain\PageBuilder\Registry\CatalogSourceRegistry;
use PHPUnit\Framework\TestCase;

final class GetCatalogSourceDefinitionsQueryTest extends TestCase
{
    public function test_it_serializes_catalog_source_definitions(): void
    {
        $query =
            new GetCatalogSourceDefinitionsQuery(
                new CatalogSourceRegistry,
            );

        $definitions = $query->handle();

        $this->assertCount(3, $definitions);

        $this->assertSame(
            [
                'type' => 'featured',
                'label' => 'Featured Products',
                'description' => 'Automatically display products marked as featured in the catalog.',
            ],
            $definitions[0],
        );

        $this->assertSame(
            'manual',
            $definitions[1]['type'],
        );

        $this->assertSame(
            'category',
            $definitions[2]['type'],
        );
    }
}
