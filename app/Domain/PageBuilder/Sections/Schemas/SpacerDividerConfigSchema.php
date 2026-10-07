<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class SpacerDividerConfigSchema implements SectionConfigSchema
{
    private const MAX_SPACER_HEIGHT = 320;

    private const MAX_LABEL_LENGTH = 120;

    private const ALLOWED_LINE_STYLES = [
        'solid',
        'dashed',
        'dotted',
    ];

    private const ALLOWED_WIDTHS = [
        'full',
        'three_quarter',
        'half',
    ];

    private const ALLOWED_ALIGNMENTS = [
        'left',
        'center',
        'right',
    ];

    private const ALLOWED_LABEL_STYLES = [
        'plain',
        'pill',
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(
        array $config,
    ): array {
        /** @var array<string, list<string>> $errors */
        $errors = [];

        $allowedKeys = [
            'mobile_height',
            'tablet_height',
            'desktop_height',

            'line_style',
            'line_color',
            'line_thickness',

            'width',
            'alignment',

            'label',
            'label_style',
            'text_color',

            'gradient_from',
            'gradient_via',
            'gradient_to',
        ];

        foreach (
            array_keys(
                $config,
            ) as $key
        ) {
            if (
                ! in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                $errors[$key][] =
                    'This configuration field is not supported.';
            }
        }

        $mobileHeight =
            $this->height(
                value: $config[
                    'mobile_height'
                ] ?? null,
                key: 'mobile_height',
                errors: $errors,
            );

        $tabletHeight =
            $this->height(
                value: $config[
                    'tablet_height'
                ] ?? null,
                key: 'tablet_height',
                errors: $errors,
            );

        $desktopHeight =
            $this->height(
                value: $config[
                    'desktop_height'
                ] ?? null,
                key: 'desktop_height',
                errors: $errors,
            );

        $lineStyle =
            $this->allowedString(
                value: $config[
                    'line_style'
                ] ?? null,
                key: 'line_style',
                allowed: self::ALLOWED_LINE_STYLES,
                fallback: 'solid',
                errors: $errors,
            );

        $lineColor =
            $this->hexColor(
                value: $config[
                    'line_color'
                ] ?? null,
                key: 'line_color',
                fallback: '#e2e8f0',
                errors: $errors,
            );

        $lineThickness =
            $this->lineThickness(
                value: $config[
                    'line_thickness'
                ] ?? null,
                errors: $errors,
            );

        $width =
            $this->allowedString(
                value: $config[
                    'width'
                ] ?? null,
                key: 'width',
                allowed: self::ALLOWED_WIDTHS,
                fallback: 'full',
                errors: $errors,
            );

        $alignment =
            $this->allowedString(
                value: $config[
                    'alignment'
                ] ?? null,
                key: 'alignment',
                allowed: self::ALLOWED_ALIGNMENTS,
                fallback: 'center',
                errors: $errors,
            );

        $label =
            $this->nullableString(
                value: $config[
                    'label'
                ] ?? null,
                key: 'label',
                maxLength: self::MAX_LABEL_LENGTH,
                errors: $errors,
            );

        $labelStyle =
            $this->allowedString(
                value: $config[
                    'label_style'
                ] ?? null,
                key: 'label_style',
                allowed: self::ALLOWED_LABEL_STYLES,
                fallback: 'plain',
                errors: $errors,
            );

        $textColor =
            $this->hexColor(
                value: $config[
                    'text_color'
                ] ?? null,
                key: 'text_color',
                fallback: '#64748b',
                errors: $errors,
            );

        $gradientFrom =
            $this->hexColor(
                value: $config[
                    'gradient_from'
                ] ?? null,
                key: 'gradient_from',
                fallback: '#6366f1',
                errors: $errors,
            );

        $gradientVia =
            $this->hexColor(
                value: $config[
                    'gradient_via'
                ] ?? null,
                key: 'gradient_via',
                fallback: '#8b5cf6',
                errors: $errors,
            );

        $gradientTo =
            $this->hexColor(
                value: $config[
                    'gradient_to'
                ] ?? null,
                key: 'gradient_to',
                fallback: '#ec4899',
                errors: $errors,
            );

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        return [
            'mobile_height' => $mobileHeight,

            'tablet_height' => $tabletHeight,

            'desktop_height' => $desktopHeight,

            'line_style' => $lineStyle,

            'line_color' => $lineColor,

            'line_thickness' => $lineThickness,

            'width' => $width,

            'alignment' => $alignment,

            'label' => $label,

            'label_style' => $labelStyle,

            'text_color' => $textColor,

            'gradient_from' => $gradientFrom,

            'gradient_via' => $gradientVia,

            'gradient_to' => $gradientTo,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function height(
        mixed $value,
        string $key,
        array &$errors,
    ): int {
        if (
            ! is_int(
                $value,
            ) ||
            $value < 0 ||
            $value >
            self::MAX_SPACER_HEIGHT
        ) {
            $errors[$key][] =
                'The spacer height must be between 0 and 320 pixels.';

            return 0;
        }

        return $value;
    }

    /**
     * @param  list<string>  $allowed
     * @param  array<string, list<string>>  $errors
     */
    private function allowedString(
        mixed $value,
        string $key,
        array $allowed,
        string $fallback,
        array &$errors,
    ): string {
        if (
            ! is_string(
                $value,
            ) ||
            ! in_array(
                $value,
                $allowed,
                true,
            )
        ) {
            $errors[$key][] =
                "The selected {$key} is not supported.";

            return $fallback;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function lineThickness(
        mixed $value,
        array &$errors,
    ): int {
        if (
            ! is_int(
                $value,
            ) ||
            $value < 1 ||
            $value > 4
        ) {
            $errors[
                'line_thickness'
            ][] =
                'The line thickness must be between 1 and 4 pixels.';

            return 1;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function nullableString(
        mixed $value,
        string $key,
        int $maxLength,
        array &$errors,
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        if (
            ! is_string(
                $value,
            )
        ) {
            $errors[$key][] =
                "The {$key} field must be a string or null.";

            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            $value === ''
        ) {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) >
            $maxLength
        ) {
            $errors[$key][] =
                "The {$key} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function hexColor(
        mixed $value,
        string $key,
        string $fallback,
        array &$errors,
    ): string {
        if (
            ! is_string(
                $value,
            )
        ) {
            $errors[$key][] =
                "The {$key} field is required and must be a string.";

            return $fallback;
        }

        $value =
            strtolower(
                trim(
                    $value,
                ),
            );

        if (
            preg_match(
                '/^#[0-9a-f]{6}$/',
                $value,
            ) !== 1
        ) {
            $errors[$key][] =
                "The {$key} must be a 6-digit hexadecimal color.";

            return $fallback;
        }

        return $value;
    }
}
