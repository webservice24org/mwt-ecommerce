<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\FeaturedProductsSection;
use App\Domain\PageBuilder\Sections\HeroSection;
use App\Domain\PageBuilder\Sections\ProductCategoriesSection;
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

    public function test_product_categories_section_can_be_resolved(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $this->assertInstanceOf(
            ProductCategoriesSection::class,
            $definition,
        );

        $this->assertSame(
            SectionType::ProductCategories,
            $definition->type(),
        );

        $this->assertSame(
            'Product Categories',
            $definition->label(),
        );
    }

    public function test_registry_reports_only_implemented_sections(): void
    {
        $registry = new SectionRegistry;

        $this->assertTrue(
            $registry->has(
                SectionType::Hero,
            ),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::FeaturedProducts,
            ),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::ProductCategories,
            ),
        );

        $this->assertFalse(
            $registry->has(
                SectionType::ProductGrid,
            ),
        );

        $this->assertFalse(
            $registry->has(
                SectionType::CategoryProducts,
            ),
        );

        $this->assertFalse(
            $registry->has(
                SectionType::CategoryShowcase,
            ),
        );

        $this->assertFalse(
            $registry->has(
                SectionType::PromotionalBanner,
            ),
        );

        $this->assertFalse(
            $registry->has(
                SectionType::NewArrivals,
            ),
        );
    }

    public function test_registry_only_returns_registered_definitions(): void
    {
        $registry = new SectionRegistry;

        $definitions = $registry->all();

        $this->assertCount(
            3,
            $definitions,
        );

        $this->assertInstanceOf(
            HeroSection::class,
            $definitions[0],
        );

        $this->assertInstanceOf(
            FeaturedProductsSection::class,
            $definitions[1],
        );

        $this->assertInstanceOf(
            ProductCategoriesSection::class,
            $definitions[2],
        );
    }

    public function test_unregistered_section_cannot_be_resolved(): void
    {
        $registry = new SectionRegistry;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $registry->get(
            SectionType::ProductGrid,
        );
    }

    public function test_featured_products_exposes_typed_templates(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::FeaturedProducts,
        );

        $templates = $definition->templates();

        $this->assertCount(
            1,
            $templates,
        );

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

    public function test_product_categories_exposes_three_typed_templates(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $templates = $definition->templates();

        $this->assertCount(
            3,
            $templates,
        );

        foreach ($templates as $template) {
            $this->assertInstanceOf(
                SectionTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'grid',
                'cards',
                'carousel',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->key,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'Category Grid',
                'Category Cards',
                'Category Carousel',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->label,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'Categories',
                'Categories',
                'Categories',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_default_template_is_supported(): void
    {
        $registry = new SectionRegistry;

        foreach (
            $registry->all() as $definition
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    $definition->type(),
                    $definition->defaultTemplate(),
                ),
            );
        }
    }

    public function test_product_categories_supports_all_registered_templates(): void
    {
        $registry = new SectionRegistry;

        foreach (
            [
                'grid',
                'cards',
                'carousel',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::ProductCategories,
                    $template,
                ),
            );
        }
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

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::ProductCategories,
                'unknown',
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
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $definition->defaultConfig(),
        );
    }

    public function test_product_categories_has_safe_default_configuration(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $this->assertSame(
            'grid',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            [
                'title' => 'Shop by Category',
                'category_ids' => [],
                'show_name' => true,
                'columns' => 4,
                'show_product_count' => false,
            ],
            $definition->defaultConfig(),
        );

        $this->assertSame(
            [
                'title' => 'Shop by Category',
                'category_ids' => [],
                'show_name' => true,
                'columns' => 4,
                'show_product_count' => false,
            ],
            $definition->defaultConfigForTemplate(
                'grid',
            ),
        );

        $this->assertSame(
            [
                'title' => 'Shop by Category',
                'category_ids' => [],
                'show_name' => true,
                'columns' => 3,
                'show_product_count' => true,
            ],
            $definition->defaultConfigForTemplate(
                'cards',
            ),
        );

        $this->assertSame(
            [
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
            ],
            $definition->defaultConfigForTemplate(
                'carousel',
            ),
        );
    }

    public function test_product_categories_rejects_unknown_default_template(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::ProductCategories,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $definition
            ->defaultConfigForTemplate(
                'unknown',
            );
    }

    public function test_registered_section_defaults_are_valid(): void
    {
        $registry = new SectionRegistry;

        foreach (
            $registry->all() as $definition
        ) {
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
