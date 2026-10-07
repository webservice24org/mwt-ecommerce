<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\TestimonialsSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TestimonialsSectionTest extends TestCase
{
    public function test_testimonials_section_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertTrue(
            $registry->has(
                SectionType::Testimonials,
            ),
        );

        $this->assertInstanceOf(
            TestimonialsSection::class,
            $registry->get(
                SectionType::Testimonials,
            ),
        );
    }

    public function test_testimonials_exposes_three_slider_templates(): void
    {
        $definition =
            new TestimonialsSection;

        $templates =
            $definition->templates();

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
                'grid_slider',
                'spotlight_slider',
                'card_slider',
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
                'Multi-Card Grid Slider',
                'Single Spotlight Slider',
                'Horizontal Card Slider',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->label,
                $templates,
            ),
        );
    }

    public function test_grid_slider_is_default_template(): void
    {
        $definition =
            new TestimonialsSection;

        $this->assertSame(
            'grid_slider',
            $definition->defaultTemplate(),
        );
    }

    public function test_every_testimonial_template_defaults_to_autoplay(): void
    {
        $definition =
            new TestimonialsSection;

        foreach (
            [
                'grid_slider',
                'spotlight_slider',
                'card_slider',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertTrue(
                $config['autoplay'],
            );

            $this->assertSame(
                5000,
                $config[
                    'autoplay_interval'
                ],
            );

            $this->assertTrue(
                $config[
                    'pause_on_hover'
                ],
            );

            $this->assertTrue(
                $config['loop'],
            );

            $this->assertTrue(
                $config[
                    'show_arrows'
                ],
            );

            $this->assertTrue(
                $config[
                    'show_dots'
                ],
            );
        }
    }

    public function test_slide_effect_defaults_match_shared_slide_transition_contract(): void
    {
        $definition =
            new TestimonialsSection;

        $allowed = [
            'none',
            'fade',
            'slide_left',
            'slide_right',
            'slide_up',
            'slide_down',
        ];

        foreach (
            $definition->templateDefaultConfigs() as $config
        ) {
            $this->assertTrue(
                in_array(
                    $config[
                        'slide_effect'
                    ],
                    $allowed,
                    true,
                ),
            );
        }

        $this->assertSame(
            'fade',
            $definition
                ->defaultConfigForTemplate(
                    'spotlight_slider',
                )[
                    'slide_effect'
                ],
        );
    }

    public function test_every_template_default_configuration_is_valid(): void
    {
        $definition =
            new TestimonialsSection;

        foreach (
            $definition->templateDefaultConfigs() as $template => $config
        ) {
            $this->assertSame(
                $config,
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    ),
                "Invalid Testimonials defaults for template [{$template}].",
            );
        }
    }

    public function test_unknown_testimonials_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new TestimonialsSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
