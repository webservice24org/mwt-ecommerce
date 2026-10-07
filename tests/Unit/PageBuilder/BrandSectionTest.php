<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\BrandSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BrandSectionTest extends TestCase
{
    public function test_brand_section_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertCount(
            11,
            $registry->all(),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::Brands,
            ),
        );

        $this->assertInstanceOf(
            BrandSection::class,
            $registry->get(
                SectionType::Brands,
            ),
        );
    }

    public function test_brand_section_exposes_four_templates(): void
    {
        $definition =
            new BrandSection;

        $templates =
            $definition->templates();

        $this->assertCount(
            4,
            $templates,
        );

        foreach (
            $templates as $template
        ) {
            $this->assertInstanceOf(
                SectionTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->key,
                $templates,
            ),
        );
    }

    public function test_brand_section_uses_logo_strip_as_default_template(): void
    {
        $definition =
            new BrandSection;

        $this->assertSame(
            'logo_strip',
            $definition->defaultTemplate(),
        );

        $this->assertSame(
            $definition
                ->defaultConfigForTemplate(
                    'logo_strip',
                ),
            $definition->defaultConfig(),
        );
    }

    public function test_all_brand_template_defaults_are_valid(): void
    {
        $definition =
            new BrandSection;

        foreach (
            [
                'logo_strip',
                'brand_cards',
                'logo_marquee',
                'spotlight_banner',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertSame(
                $config,
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    ),
            );
        }
    }

    public function test_brand_templates_have_reference_design_defaults(): void
    {
        $definition =
            new BrandSection;

        $strip =
            $definition
                ->defaultConfigForTemplate(
                    'logo_strip',
                );

        $cards =
            $definition
                ->defaultConfigForTemplate(
                    'brand_cards',
                );

        $marquee =
            $definition
                ->defaultConfigForTemplate(
                    'logo_marquee',
                );

        $spotlight =
            $definition
                ->defaultConfigForTemplate(
                    'spotlight_banner',
                );

        $this->assertSame(
            6,
            $strip['columns'],
        );

        $this->assertFalse(
            $strip['show_name'],
        );

        $this->assertTrue(
            $cards['show_name'],
        );

        $this->assertTrue(
            $cards[
                'show_description'
            ],
        );

        $this->assertTrue(
            $cards[
                'show_product_count'
            ],
        );

        $this->assertSame(
            25,
            $marquee[
                'marquee_duration'
            ],
        );

        $this->assertTrue(
            $marquee[
                'pause_on_hover'
            ],
        );

        $this->assertSame(
            '#312e81',
            $spotlight[
                'background_color'
            ],
        );

        $this->assertSame(
            'light',
            $spotlight[
                'text_theme'
            ],
        );

        $this->assertSame(
            4,
            $spotlight['limit'],
        );
    }

    public function test_unknown_brand_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new BrandSection
        )->defaultConfigForTemplate(
            'invalid-template',
        );
    }
}
