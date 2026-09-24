<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\FeaturedProductsSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SectionRegistryTest extends TestCase
{
    public function test_registered_section_can_be_resolved(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $this->assertInstanceOf(
            FeaturedProductsSection::class,
            $definition,
        );

        $this->assertSame(
            SectionType::FeaturedProducts,
            $definition->type(),
        );
    }

    public function test_registry_reports_only_implemented_sections(): void
    {
        $registry = new SectionRegistry;

        $this->assertTrue(
            $registry->has(SectionType::FeaturedProducts),
        );

        $this->assertFalse(
            $registry->has(SectionType::Hero),
        );
    }

    public function test_registry_only_returns_registered_definitions(): void
    {
        $registry = new SectionRegistry;

        $definitions = $registry->all();

        $this->assertCount(1, $definitions);

        $this->assertInstanceOf(
            FeaturedProductsSection::class,
            $definitions[0],
        );
    }

    public function test_unregistered_section_cannot_be_resolved(): void
    {
        $registry = new SectionRegistry;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->get(SectionType::Hero);
    }

    public function test_featured_products_exposes_typed_templates(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $templates = $definition->templates();

        $this->assertCount(1, $templates);

        $this->assertInstanceOf(
            SectionTemplateData::class,
            $templates[0],
        );

        $this->assertSame(
            'grid',
            $templates[0]->key,
        );

        $this->assertSame(
            'Product Grid',
            $templates[0]->label,
        );

        $this->assertSame(
            'Display a curated selection of featured products in a responsive grid.',
            $templates[0]->description,
        );

        $this->assertSame(
            'Products',
            $templates[0]->category,
        );
    }

    public function test_default_template_is_supported(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $this->assertTrue(
            $registry->supportsTemplate(
                $definition->type(),
                $definition->defaultTemplate(),
            ),
        );
    }

    public function test_unknown_template_is_not_supported(): void
    {
        $registry = new SectionRegistry;

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::FeaturedProducts,
                'unknown',
            ),
        );

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::Hero,
                'grid',
            ),
        );
    }

    public function test_featured_products_has_safe_default_configuration(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            $definition->defaultConfig(),
        );
    }

    public function test_registered_section_defaults_are_valid(): void
    {
        $registry = new SectionRegistry;

        foreach ($registry->all() as $definition) {
            $validated = $definition
                ->configSchema()
                ->validate(
                    $definition->defaultConfig(),
                );

            $this->assertSame(
                $definition->defaultConfig(),
                $validated,
            );

            $this->assertTrue(
                $registry->supportsTemplate(
                    $definition->type(),
                    $definition->defaultTemplate(),
                ),
            );
        }
    }
}
