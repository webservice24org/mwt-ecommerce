<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class HeroSectionTest extends TestCase
{
    public function test_hero_is_registered(): void
    {
        $registry = new SectionRegistry;

        $this->assertTrue(
            $registry->has(
                SectionType::Hero,
            ),
        );
    }

    public function test_hero_exposes_three_templates(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $templates = $definition->templates();

        $this->assertCount(
            3,
            $templates,
        );

        $this->assertSame(
            'content_slider',
            $templates[0]->key,
        );

        $this->assertSame(
            'Content Slider',
            $templates[0]->label,
        );

        $this->assertSame(
            'image_slider',
            $templates[1]->key,
        );

        $this->assertSame(
            'Image Slider',
            $templates[1]->label,
        );

        $this->assertSame(
            'static',
            $templates[2]->key,
        );

        $this->assertSame(
            'Static Hero',
            $templates[2]->label,
        );
    }

    public function test_content_slider_is_default_template(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $this->assertSame(
            'content_slider',
            $definition->defaultTemplate(),
        );
    }

    public function test_hero_has_safe_default_configuration(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $this->assertSame(
            [
                'autoplay' => true,
                'autoplay_delay' => 5000,
                'effect' => 'slide_left',
                'show_arrows' => true,
                'show_dots' => true,

                'slides' => [
                    [
                        'background_color' => '#111827',
                        'background_image' => null,

                        'top_title' => 'New Collection',
                        'title' => 'Discover Something New',
                        'description' => 'Explore our latest products and collections.',

                        'alignment' => 'left',

                        'primary_button' => [
                            'label' => 'Shop Now',
                            'url' => '/products',
                        ],

                        'secondary_button' => null,
                    ],
                ],
            ],
            $definition->defaultConfig(),
        );
    }

    public function test_registry_supports_all_hero_templates(): void
    {
        $registry = new SectionRegistry;

        $this->assertTrue(
            $registry->supportsTemplate(
                SectionType::Hero,
                'content_slider',
            ),
        );

        $this->assertTrue(
            $registry->supportsTemplate(
                SectionType::Hero,
                'image_slider',
            ),
        );

        $this->assertTrue(
            $registry->supportsTemplate(
                SectionType::Hero,
                'static',
            ),
        );

        $this->assertFalse(
            $registry->supportsTemplate(
                SectionType::Hero,
                'unknown',
            ),
        );
    }

    public function test_content_slider_has_template_specific_defaults(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $config = $definition->defaultConfigForTemplate(
            'content_slider',
        );

        $this->assertTrue(
            $config['autoplay'],
        );

        $this->assertSame(
            'slide_left',
            $config['effect'],
        );

        $this->assertTrue(
            $config['show_arrows'],
        );

        $this->assertTrue(
            $config['show_dots'],
        );

        $this->assertSame(
            'Discover Something New',
            $config['slides'][0]['title'],
        );
    }

    public function test_image_slider_has_template_specific_defaults(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $config = $definition->defaultConfigForTemplate(
            'image_slider',
        );

        $this->assertTrue(
            $config['autoplay'],
        );

        $this->assertSame(
            'slide_left',
            $config['effect'],
        );

        $this->assertArrayHasKey(
            'image',
            $config['slides'][0],
        );

        $this->assertNull(
            $config['slides'][0]['image'],
        );

        $this->assertSame(
            '',
            $config['slides'][0]['alt'],
        );

        $this->assertSame(
            '',
            $config['slides'][0]['url'],
        );
    }

    public function test_static_hero_has_template_specific_defaults(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $config = $definition->defaultConfigForTemplate(
            'static',
        );

        $this->assertFalse(
            $config['autoplay'],
        );

        $this->assertFalse(
            $config['show_arrows'],
        );

        $this->assertFalse(
            $config['show_dots'],
        );

        $this->assertCount(
            1,
            $config['slides'],
        );

        $this->assertSame(
            'Discover Our Store',
            $config['slides'][0]['title'],
        );
    }

    public function test_unknown_hero_template_has_no_default_configuration(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $this->expectException(
            \InvalidArgumentException::class,
        );

        $definition->defaultConfigForTemplate(
            'unknown',
        );
    }

    public function test_default_config_matches_default_template_config(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        $this->assertSame(
            $definition->defaultConfigForTemplate(
                $definition->defaultTemplate(),
            ),
            $definition->defaultConfig(),
        );
    }

    public function test_every_hero_template_default_configuration_is_valid(): void
    {
        $registry = new SectionRegistry;

        $definition = $registry->get(
            SectionType::Hero,
        );

        foreach ($definition->templates() as $template) {
            $config = $definition->defaultConfigForTemplate(
                $template->key,
            );

            $validated = $definition
                ->configSchema()
                ->validate($config);

            $this->assertSame(
                $config,
                $validated,
            );
        }
    }
}
