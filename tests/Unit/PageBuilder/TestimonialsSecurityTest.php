<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\TestimonialsSection;
use PHPUnit\Framework\TestCase;

final class TestimonialsSecurityTest extends TestCase
{
    public function test_safe_testimonial_image_urls_are_accepted(): void
    {
        $definition =
            new TestimonialsSection;

        foreach (
            [
                '/storage/page-builder/testimonial.jpg',
                'https://cdn.example.com/customer.webp',
                'http://localhost/storage/customer.png',
            ] as $image
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        'grid_slider',
                    );

            $config[
                'items'
            ][0][
                'image'
            ] =
                $image;

            $validated =
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    );

            $this->assertSame(
                $image,
                $validated[
                    'items'
                ][0][
                    'image'
                ],
            );
        }
    }

    public function test_unsafe_testimonial_image_urls_are_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        foreach (
            [
                'javascript:alert(1)',
                'data:image/svg+xml,<svg></svg>',
                'file:///etc/passwd',
                'ftp://example.com/avatar.jpg',
                '//evil.example/avatar.jpg',
                '\\evil.example\\avatar.jpg',
                'https://user:password@example.com/avatar.jpg',
                "https://example.com/avatar.jpg\njavascript:alert(1)",
            ] as $image
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        'grid_slider',
                    );

            $config[
                'items'
            ][0][
                'image'
            ] =
                $image;

            $this->assertRejected(
                $definition,
                $config,
            );
        }
    }

    public function test_unknown_top_level_field_is_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        $config =
            $definition
                ->defaultConfigForTemplate(
                    'grid_slider',
                );

        $config[
            'dangerous_option'
        ] =
            true;

        $this->assertRejected(
            $definition,
            $config,
        );
    }

    public function test_unknown_testimonial_field_is_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        $config =
            $definition
                ->defaultConfigForTemplate(
                    'grid_slider',
                );

        $config[
            'items'
        ][0][
            'html'
        ] =
            '<script>alert(1)</script>';

        $this->assertRejected(
            $definition,
            $config,
        );
    }

    public function test_more_than_twelve_testimonials_are_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        $config =
            $definition
                ->defaultConfigForTemplate(
                    'grid_slider',
                );

        $item =
            $config[
                'items'
            ][0];

        $config[
            'items'
        ] =
            array_fill(
                0,
                13,
                $item,
            );

        $this->assertRejected(
            $definition,
            $config,
        );
    }

    public function test_invalid_ratings_are_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        foreach (
            [
                0,
                6,
                -1,
                '5',
                4.5,
            ] as $rating
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        'grid_slider',
                    );

            $config[
                'items'
            ][0][
                'rating'
            ] =
                $rating;

            $this->assertRejected(
                $definition,
                $config,
            );
        }
    }

    public function test_invalid_slider_options_are_rejected(): void
    {
        $definition =
            new TestimonialsSection;

        $invalidOptions = [
            [
                'autoplay_interval',
                1999,
            ],
            [
                'autoplay_interval',
                20001,
            ],
            [
                'autoplay_interval',
                '5000',
            ],
            [
                'autoplay',
                'true',
            ],
            [
                'pause_on_hover',
                1,
            ],
            [
                'loop',
                'yes',
            ],
            [
                'show_arrows',
                1,
            ],
            [
                'show_dots',
                0,
            ],
            [
                'slide_effect',
                'javascript',
            ],
        ];

        foreach (
            $invalidOptions as [
                $key,
                $value,
            ]
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        'grid_slider',
                    );

            $config[
                $key
            ] =
                $value;

            $this->assertRejected(
                $definition,
                $config,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertRejected(
        TestimonialsSection $definition,
        array $config,
    ): void {
        try {
            $definition
                ->configSchema()
                ->validate(
                    $config,
                );

            $this->fail(
                'Expected the Testimonials configuration to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration
        ) {
            $this->assertTrue(
                true,
            );
        }
    }
}
