<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\SpacerDividerConfigSchema;
use App\Domain\PageBuilder\Sections\SpacerDividerSection;
use PHPUnit\Framework\TestCase;

final class SpacerDividerConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $config =
            $this->validConfig();

        $config['line_color'] =
            '  #AABBCC  ';

        $config['text_color'] =
            ' #445566 ';

        $config['gradient_from'] =
            ' #112233 ';

        $config['gradient_via'] =
            ' #778899 ';

        $config['gradient_to'] =
            ' #ABCDEF ';

        $config['label'] =
            '  Featured Collection  ';

        $validated =
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            '#aabbcc',
            $validated[
                'line_color'
            ],
        );

        $this->assertSame(
            '#445566',
            $validated[
                'text_color'
            ],
        );

        $this->assertSame(
            '#112233',
            $validated[
                'gradient_from'
            ],
        );

        $this->assertSame(
            '#778899',
            $validated[
                'gradient_via'
            ],
        );

        $this->assertSame(
            '#abcdef',
            $validated[
                'gradient_to'
            ],
        );

        $this->assertSame(
            'Featured Collection',
            $validated[
                'label'
            ],
        );
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['custom_css'] =
            'position:fixed';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'custom_css',
        );
    }

    public function test_mobile_height_must_be_an_integer(): void
    {
        $config =
            $this->validConfig();

        $config['mobile_height'] =
            '32';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'mobile_height',
        );
    }

    public function test_tablet_height_must_be_an_integer(): void
    {
        $config =
            $this->validConfig();

        $config['tablet_height'] =
            64.5;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'tablet_height',
        );
    }

    public function test_desktop_height_must_be_an_integer(): void
    {
        $config =
            $this->validConfig();

        $config['desktop_height'] =
            null;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'desktop_height',
        );
    }

    public function test_negative_spacer_height_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['mobile_height'] =
            -1;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'mobile_height',
        );
    }

    public function test_spacer_height_above_320_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['desktop_height'] =
            321;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'desktop_height',
        );
    }

    public function test_height_boundaries_are_accepted(): void
    {
        $config =
            $this->validConfig();

        $config['mobile_height'] =
            0;

        $config['tablet_height'] =
            320;

        $config['desktop_height'] =
            320;

        $validated =
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            0,
            $validated[
                'mobile_height'
            ],
        );

        $this->assertSame(
            320,
            $validated[
                'tablet_height'
            ],
        );

        $this->assertSame(
            320,
            $validated[
                'desktop_height'
            ],
        );
    }

    public function test_invalid_line_style_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['line_style'] =
            'double';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'line_style',
        );
    }

    public function test_supported_line_styles_are_accepted(): void
    {
        foreach (
            [
                'solid',
                'dashed',
                'dotted',
            ] as $style
        ) {
            $config =
                $this->validConfig();

            $config['line_style'] =
                $style;

            $validated =
                (
                    new SpacerDividerConfigSchema
                )->validate(
                    $config,
                );

            $this->assertSame(
                $style,
                $validated[
                    'line_style'
                ],
            );
        }
    }

    public function test_line_thickness_below_one_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['line_thickness'] =
            0;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'line_thickness',
        );
    }

    public function test_line_thickness_above_four_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['line_thickness'] =
            5;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'line_thickness',
        );
    }

    public function test_line_thickness_boundaries_are_accepted(): void
    {
        foreach (
            [
                1,
                4,
            ] as $thickness
        ) {
            $config =
                $this->validConfig();

            $config[
                'line_thickness'
            ] =
                $thickness;

            $validated =
                (
                    new SpacerDividerConfigSchema
                )->validate(
                    $config,
                );

            $this->assertSame(
                $thickness,
                $validated[
                    'line_thickness'
                ],
            );
        }
    }

    public function test_invalid_width_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['width'] =
            'quarter';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'width',
        );
    }

    public function test_supported_widths_are_accepted(): void
    {
        foreach (
            [
                'full',
                'three_quarter',
                'half',
            ] as $width
        ) {
            $config =
                $this->validConfig();

            $config['width'] =
                $width;

            $validated =
                (
                    new SpacerDividerConfigSchema
                )->validate(
                    $config,
                );

            $this->assertSame(
                $width,
                $validated[
                    'width'
                ],
            );
        }
    }

    public function test_invalid_alignment_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['alignment'] =
            'justify';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'alignment',
        );
    }

    public function test_supported_alignments_are_accepted(): void
    {
        foreach (
            [
                'left',
                'center',
                'right',
            ] as $alignment
        ) {
            $config =
                $this->validConfig();

            $config['alignment'] =
                $alignment;

            $validated =
                (
                    new SpacerDividerConfigSchema
                )->validate(
                    $config,
                );

            $this->assertSame(
                $alignment,
                $validated[
                    'alignment'
                ],
            );
        }
    }

    public function test_invalid_label_style_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['label_style'] =
            'html';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'label_style',
        );
    }

    public function test_supported_label_styles_are_accepted(): void
    {
        foreach (
            [
                'plain',
                'pill',
            ] as $style
        ) {
            $config =
                $this->validConfig();

            $config[
                'label_style'
            ] =
                $style;

            $validated =
                (
                    new SpacerDividerConfigSchema
                )->validate(
                    $config,
                );

            $this->assertSame(
                $style,
                $validated[
                    'label_style'
                ],
            );
        }
    }

    public function test_blank_label_is_normalized_to_null(): void
    {
        $config =
            $this->validConfig();

        $config['label'] =
            '   ';

        $validated =
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

        $this->assertNull(
            $validated[
                'label'
            ],
        );
    }

    public function test_label_must_be_string_or_null(): void
    {
        $config =
            $this->validConfig();

        $config['label'] =
            123;

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'label',
        );
    }

    public function test_label_length_is_limited(): void
    {
        $config =
            $this->validConfig();

        $config['label'] =
            str_repeat(
                'a',
                121,
            );

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'label',
        );
    }

    public function test_label_boundary_length_is_accepted(): void
    {
        $config =
            $this->validConfig();

        $config['label'] =
            str_repeat(
                'a',
                120,
            );

        $validated =
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            120,
            mb_strlen(
                $validated[
                    'label'
                ] ?? '',
            ),
        );
    }

    public function test_invalid_line_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['line_color'] =
            'red';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'line_color',
        );
    }

    public function test_short_hex_line_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['line_color'] =
            '#fff';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'line_color',
        );
    }

    public function test_invalid_text_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['text_color'] =
            'rgb(0,0,0)';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'text_color',
        );
    }

    public function test_invalid_gradient_from_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['gradient_from'] =
            'transparent';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'gradient_from',
        );
    }

    public function test_invalid_gradient_via_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['gradient_via'] =
            'var(--brand)';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'gradient_via',
        );
    }

    public function test_invalid_gradient_to_color_is_rejected(): void
    {
        $config =
            $this->validConfig();

        $config['gradient_to'] =
            '#12345g';

        $this->assertInvalidConfig(
            config: $config,
            errorKey: 'gradient_to',
        );
    }

    public function test_all_exact_six_digit_hex_colors_are_accepted(): void
    {
        $config =
            $this->validConfig();

        $config['line_color'] =
            '#123456';

        $config['text_color'] =
            '#abcdef';

        $config['gradient_from'] =
            '#010203';

        $config['gradient_via'] =
            '#a1b2c3';

        $config['gradient_to'] =
            '#fedcba';

        $validated =
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

        $this->assertSame(
            '#123456',
            $validated[
                'line_color'
            ],
        );

        $this->assertSame(
            '#abcdef',
            $validated[
                'text_color'
            ],
        );

        $this->assertSame(
            '#010203',
            $validated[
                'gradient_from'
            ],
        );

        $this->assertSame(
            '#a1b2c3',
            $validated[
                'gradient_via'
            ],
        );

        $this->assertSame(
            '#fedcba',
            $validated[
                'gradient_to'
            ],
        );
    }

    private function validConfig(): array
    {
        return (
            new SpacerDividerSection
        )->defaultConfigForTemplate(
            'responsive_spacer',
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertInvalidConfig(
        array $config,
        string $errorKey,
    ): void {
        try {
            (
                new SpacerDividerConfigSchema
            )->validate(
                $config,
            );

            $this->fail(
                sprintf(
                    'Expected Spacer / Divider configuration field [%s] to be rejected.',
                    $errorKey,
                ),
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                $errorKey,
                $exception->errors(),
            );
        }
    }
}
