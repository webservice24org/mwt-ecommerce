<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Registry\CatalogSourceRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogSourceRegistryTest extends TestCase
{
    public function test_it_exposes_supported_catalog_sources(): void
    {
        $registry = new CatalogSourceRegistry;

        $definitions = $registry->all();

        $this->assertCount(3, $definitions);

        $this->assertSame(
            [
                CatalogSourceType::Featured,
                CatalogSourceType::Manual,
                CatalogSourceType::Category,
            ],
            array_map(
                static fn ($definition) => $definition->type,
                $definitions,
            ),
        );
    }

    public function test_it_can_determine_whether_a_source_is_supported(): void
    {
        $registry = new CatalogSourceRegistry;

        $this->assertTrue(
            $registry->has('featured'),
        );

        $this->assertTrue(
            $registry->has('manual'),
        );

        $this->assertTrue(
            $registry->has('category'),
        );

        $this->assertFalse(
            $registry->has('unknown'),
        );
    }

    public function test_it_returns_a_definition_for_a_supported_source(): void
    {
        $registry = new CatalogSourceRegistry;

        $definition =
            $registry->get('manual');

        $this->assertNotNull($definition);
        $this->assertSame(
            CatalogSourceType::Manual,
            $definition->type,
        );
        $this->assertSame(
            'Manual Selection',
            $definition->label,
        );
    }

    public function test_it_returns_null_for_an_unknown_source(): void
    {
        $registry = new CatalogSourceRegistry;

        $this->assertNull(
            $registry->get('unknown'),
        );
    }
}
