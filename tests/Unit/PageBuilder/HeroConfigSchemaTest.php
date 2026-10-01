<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\HeroConfigSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HeroConfigSchemaTest extends TestCase
{
    private HeroConfigSchema $schema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = new HeroConfigSchema;
    }

    public function test_valid_configuration_is_normalized(): void
    {
        $config = $this->schema->validate([
            'autoplay' => true,
            'autoplay_delay' => 6000,
            'effect' => 'fade_scale',
            'show_arrows' => true,
            'show_dots' => false,
            'slides' => [
                [
                    'background_color' => '  #111827  ',
                    'background_image' => null,
                    'top_title' => '  New Collection  ',
                    'title' => '  Summer Fashion  ',
                    'description' => '  Discover our collection.  ',
                    'alignment' => 'center',
                    'primary_button' => [
                        'label' => '  Shop Now  ',
                        'url' => '  /products  ',
                    ],
                    'secondary_button' => null,
                ],
            ],
        ]);

        $this->assertSame(
            [
                'autoplay' => true,
                'autoplay_delay' => 6000,
                'effect' => 'fade_scale',
                'show_arrows' => true,
                'show_dots' => false,
                'slides' => [
                    [
                        'background_color' => '#111827',
                        'background_image' => null,
                        'top_title' => 'New Collection',
                        'title' => 'Summer Fashion',
                        'description' => 'Discover our collection.',
                        'alignment' => 'center',
                        'primary_button' => [
                            'label' => 'Shop Now',
                            'url' => '/products',
                        ],
                        'secondary_button' => null,
                    ],
                ],
            ],
            $config,
        );
    }

    public function test_missing_optional_slider_settings_receive_defaults(): void
    {
        $config = $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);

        $this->assertSame(
            true,
            $config['autoplay'],
        );

        $this->assertSame(
            5000,
            $config['autoplay_delay'],
        );

        $this->assertSame(
            'slide_left',
            $config['effect'],
        );

        $this->assertSame(
            true,
            $config['show_arrows'],
        );

        $this->assertSame(
            true,
            $config['show_dots'],
        );

        $this->assertSame(
            'left',
            $config['slides'][0]['alignment'],
        );

        $this->assertNull(
            $config['slides'][0]['primary_button'],
        );

        $this->assertNull(
            $config['slides'][0]['secondary_button'],
        );
    }

    #[DataProvider('supportedEffects')]
    public function test_supported_effect_is_accepted(
        string $effect,
    ): void {
        $config = $this->schema->validate([
            'effect' => $effect,
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);

        $this->assertSame(
            $effect,
            $config['effect'],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedEffects(): array
    {
        return [
            'slide left' => ['slide_left'],
            'slide right' => ['slide_right'],
            'slide up' => ['slide_up'],
            'slide down' => ['slide_down'],
            'fade' => ['fade'],
            'fade scale' => ['fade_scale'],
            'zoom' => ['zoom'],
        ];
    }

    #[DataProvider('supportedAlignments')]
    public function test_supported_alignment_is_accepted(
        string $alignment,
    ): void {
        $config = $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'alignment' => $alignment,
                ],
            ],
        ]);

        $this->assertSame(
            $alignment,
            $config['slides'][0]['alignment'],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedAlignments(): array
    {
        return [
            'left' => ['left'],
            'center' => ['center'],
            'right' => ['right'],
        ];
    }

    public function test_image_slide_fields_are_supported(): void
    {
        $config = $this->schema->validate([
            'slides' => [
                [
                    'image' => ' /storage/hero.jpg ',
                    'alt' => ' Summer collection ',
                    'url' => ' /products ',
                ],
            ],
        ]);

        $this->assertSame(
            '/storage/hero.jpg',
            $config['slides'][0]['image'],
        );

        $this->assertSame(
            'Summer collection',
            $config['slides'][0]['alt'],
        );

        $this->assertSame(
            '/products',
            $config['slides'][0]['url'],
        );
    }

    public function test_multiple_slides_are_supported(): void
    {
        $config = $this->schema->validate([
            'slides' => [
                [
                    'title' => 'First',
                ],
                [
                    'title' => 'Second',
                ],
                [
                    'image' => '/storage/third.jpg',
                ],
            ],
        ]);

        $this->assertCount(
            3,
            $config['slides'],
        );
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'unsupported' => true,
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    public function test_unknown_slide_field_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'unsupported' => true,
                ],
            ],
        ]);
    }

    public function test_slides_are_required(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([]);
    }

    public function test_empty_slides_are_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [],
        ]);
    }

    public function test_slides_must_be_an_array(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => 'invalid',
        ]);
    }

    public function test_each_slide_must_be_an_array(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                'invalid',
            ],
        ]);
    }

    public function test_invalid_effect_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'effect' => 'spin_around',
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    public function test_invalid_alignment_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'alignment' => 'justify',
                ],
            ],
        ]);
    }

    public function test_autoplay_must_be_boolean(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'autoplay' => 1,
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    public function test_show_arrows_must_be_boolean(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'show_arrows' => 'yes',
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    public function test_show_dots_must_be_boolean(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'show_dots' => 'yes',
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    public function test_autoplay_delay_must_be_integer(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'autoplay_delay' => '5000',
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    #[DataProvider('invalidAutoplayDelays')]
    public function test_autoplay_delay_must_be_within_boundary(
        int $delay,
    ): void {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'autoplay_delay' => $delay,
            'slides' => [
                [
                    'title' => 'Hero',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidAutoplayDelays(): array
    {
        return [
            'too short' => [999],
            'too long' => [30001],
        ];
    }

    public function test_button_requires_both_label_and_url(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'primary_button' => [
                        'label' => 'Shop Now',
                    ],
                ],
            ],
        ]);
    }

    public function test_empty_button_label_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'primary_button' => [
                        'label' => '   ',
                        'url' => '/products',
                    ],
                ],
            ],
        ]);
    }

    public function test_empty_button_url_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'primary_button' => [
                        'label' => 'Shop Now',
                        'url' => '   ',
                    ],
                ],
            ],
        ]);
    }

    public function test_unknown_button_field_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'primary_button' => [
                        'label' => 'Shop Now',
                        'url' => '/products',
                        'target' => '_blank',
                    ],
                ],
            ],
        ]);
    }

    public function test_optional_button_may_be_null(): void
    {
        $config = $this->schema->validate([
            'slides' => [
                [
                    'title' => 'Hero',
                    'primary_button' => null,
                    'secondary_button' => null,
                ],
            ],
        ]);

        $this->assertNull(
            $config['slides'][0]['primary_button'],
        );

        $this->assertNull(
            $config['slides'][0]['secondary_button'],
        );
    }

    public function test_slide_string_fields_must_be_strings_or_null(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->schema->validate([
            'slides' => [
                [
                    'title' => 123,
                ],
            ],
        ]);
    }
}
