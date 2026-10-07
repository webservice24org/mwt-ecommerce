<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class TestimonialsConfigSchema implements SectionConfigSchema
{
    private const MAX_EYEBROW_LENGTH = 120;

    private const MAX_HEADING_LENGTH = 180;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_ITEMS = 12;

    private const MAX_QUOTE_LENGTH = 2000;

    private const MAX_NAME_LENGTH = 120;

    private const MAX_ROLE_LENGTH = 180;

    private const MAX_BADGE_LENGTH = 120;

    private const MAX_IMAGE_LENGTH = 2048;

    private const MAX_IMAGE_ALT_LENGTH = 255;

    private const MIN_AUTOPLAY_INTERVAL = 2000;

    private const MAX_AUTOPLAY_INTERVAL = 20000;

    /**
     * These values intentionally mirror
     * SlideTransition.tsx SlideEffect.
     */
    private const SLIDE_EFFECTS = [
        'none',
        'fade',
        'slide_left',
        'slide_right',
        'slide_up',
        'slide_down',
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
            'eyebrow',
            'heading',
            'description',
            'items',
            'alignment',
            'background_color',
            'text_theme',
            'show_rating',
            'autoplay',
            'autoplay_interval',
            'pause_on_hover',
            'loop',
            'show_arrows',
            'show_dots',
            'slide_effect',
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

        $eyebrow =
            $this->nullableString(
                config: $config,
                key: 'eyebrow',
                maxLength: self::MAX_EYEBROW_LENGTH,
                label: 'eyebrow',
                errors: $errors,
            );

        $heading =
            $this->nullableString(
                config: $config,
                key: 'heading',
                maxLength: self::MAX_HEADING_LENGTH,
                label: 'heading',
                errors: $errors,
            );

        $description =
            $this->optionalString(
                config: $config,
                key: 'description',
                maxLength: self::MAX_DESCRIPTION_LENGTH,
                label: 'description',
                errors: $errors,
            );

        $items =
            $this->items(
                value: $config['items'] ??
                null,
                errors: $errors,
            );

        $alignment =
            $this->allowedString(
                value: $config['alignment'] ??
                null,
                key: 'alignment',
                allowed: [
                    'left',
                    'center',
                ],
                fallback: 'left',
                errors: $errors,
            );

        $backgroundColor =
            $this->backgroundColor(
                value: $config[
                    'background_color'
                ] ?? null,
                errors: $errors,
            );

        $textTheme =
            $this->allowedString(
                value: $config['text_theme'] ??
                null,
                key: 'text_theme',
                allowed: [
                    'light',
                    'dark',
                ],
                fallback: 'dark',
                errors: $errors,
            );

        $showRating =
            $this->boolean(
                value: $config[
                    'show_rating'
                ] ?? null,
                key: 'show_rating',
                errors: $errors,
            );

        $autoplay =
            $this->boolean(
                value: $config[
                    'autoplay'
                ] ?? null,
                key: 'autoplay',
                errors: $errors,
            );

        $autoplayInterval =
            $this->integerRange(
                value: $config[
                    'autoplay_interval'
                ] ?? null,
                key: 'autoplay_interval',
                min: self::MIN_AUTOPLAY_INTERVAL,
                max: self::MAX_AUTOPLAY_INTERVAL,
                fallback: 5000,
                errors: $errors,
            );

        $pauseOnHover =
            $this->boolean(
                value: $config[
                    'pause_on_hover'
                ] ?? null,
                key: 'pause_on_hover',
                errors: $errors,
            );

        $loop =
            $this->boolean(
                value: $config['loop'] ??
                null,
                key: 'loop',
                errors: $errors,
            );

        $showArrows =
            $this->boolean(
                value: $config[
                    'show_arrows'
                ] ?? null,
                key: 'show_arrows',
                errors: $errors,
            );

        $showDots =
            $this->boolean(
                value: $config[
                    'show_dots'
                ] ?? null,
                key: 'show_dots',
                errors: $errors,
            );

        $slideEffect =
            $this->allowedString(
                value: $config[
                    'slide_effect'
                ] ?? null,
                key: 'slide_effect',
                allowed: self::SLIDE_EFFECTS,
                fallback: 'slide_left',
                errors: $errors,
            );

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        return [
            'eyebrow' => $eyebrow,

            'heading' => $heading,

            'description' => $description,

            'items' => $items,

            'alignment' => $alignment,

            'background_color' => $backgroundColor,

            'text_theme' => $textTheme,

            'show_rating' => $showRating,

            'autoplay' => $autoplay,

            'autoplay_interval' => $autoplayInterval,

            'pause_on_hover' => $pauseOnHover,

            'loop' => $loop,

            'show_arrows' => $showArrows,

            'show_dots' => $showDots,

            'slide_effect' => $slideEffect,
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, mixed>>
     */
    private function items(
        mixed $value,
        array &$errors,
    ): array {
        if (
            ! is_array(
                $value,
            ) ||
            ! array_is_list(
                $value,
            )
        ) {
            $errors['items'][] =
                'The testimonials field is required and must be a list.';

            return [];
        }

        if ($value === []) {
            $errors['items'][] =
                'At least one testimonial is required.';
        }

        if (
            count(
                $value,
            ) > self::MAX_ITEMS
        ) {
            $errors['items'][] =
                'No more than 12 testimonials may be added.';
        }

        $normalized = [];

        foreach (
            array_slice(
                $value,
                0,
                self::MAX_ITEMS,
            ) as $index => $item
        ) {
            if (
                ! is_array(
                    $item,
                )
            ) {
                $errors[
                    "items.{$index}"
                ][] =
                    'Each testimonial must be an object.';

                continue;
            }

            $normalized[] =
                $this->item(
                    item: $item,
                    index: $index,
                    errors: $errors,
                );
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $item
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function item(
        array $item,
        int $index,
        array &$errors,
    ): array {
        $allowedKeys = [
            'quote',
            'name',
            'role',
            'rating',
            'image',
            'image_alt',
            'badge',
        ];

        foreach (
            array_keys(
                $item,
            ) as $key
        ) {
            if (
                ! is_string(
                    $key,
                ) ||
                ! in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                $errors[
                    "items.{$index}.{$key}"
                ][] =
                    'This testimonial field is not supported.';
            }
        }

        $quote =
            $this->requiredItemString(
                item: $item,
                key: 'quote',
                errorKey: "items.{$index}.quote",
                maxLength: self::MAX_QUOTE_LENGTH,
                label: 'testimonial quote',
                errors: $errors,
            );

        $name =
            $this->requiredItemString(
                item: $item,
                key: 'name',
                errorKey: "items.{$index}.name",
                maxLength: self::MAX_NAME_LENGTH,
                label: 'testimonial name',
                errors: $errors,
            );

        $role =
            $this->nullableItemString(
                item: $item,
                key: 'role',
                errorKey: "items.{$index}.role",
                maxLength: self::MAX_ROLE_LENGTH,
                label: 'testimonial role',
                errors: $errors,
            );

        $image =
            $this->nullableItemString(
                item: $item,
                key: 'image',
                errorKey: "items.{$index}.image",
                maxLength: self::MAX_IMAGE_LENGTH,
                label: 'testimonial image',
                errors: $errors,
            );

        if (
            $image !== null &&
            ! $this->isSafeMediaUrl(
                $image,
            )
        ) {
            $errors[
                "items.{$index}.image"
            ][] =
                'The testimonial image must use a safe internal path or an HTTP/HTTPS URL.';
        }

        $imageAlt =
            $this->nullableItemString(
                item: $item,
                key: 'image_alt',
                errorKey: "items.{$index}.image_alt",
                maxLength: self::MAX_IMAGE_ALT_LENGTH,
                label: 'testimonial image alt text',
                errors: $errors,
            );

        $badge =
            $this->nullableItemString(
                item: $item,
                key: 'badge',
                errorKey: "items.{$index}.badge",
                maxLength: self::MAX_BADGE_LENGTH,
                label: 'testimonial badge',
                errors: $errors,
            );

        $rating =
            $this->nullableRating(
                value: $item['rating'] ??
                null,
                key: "items.{$index}.rating",
                errors: $errors,
            );

        return [
            'quote' => $quote,

            'name' => $name,

            'role' => $role,

            'rating' => $rating,

            'image' => $image,

            'image_alt' => $imageAlt,

            'badge' => $badge,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function nullableString(
        array $config,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): ?string {
        $value =
            $config[$key] ??
            null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field must be a string or null.";

            return null;
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            $errors[$key][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function optionalString(
        array $config,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): string {
        $value =
            $config[$key] ??
            '';

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field must be a string.";

            return '';
        }

        $value =
            trim(
                $value,
            );

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            $errors[$key][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $item
     * @param  array<string, list<string>>  $errors
     */
    private function requiredItemString(
        array $item,
        string $key,
        string $errorKey,
        int $maxLength,
        string $label,
        array &$errors,
    ): string {
        $value =
            $item[$key] ??
            null;

        if (! is_string($value)) {
            $errors[$errorKey][] =
                "The {$label} field is required and must be a string.";

            return '';
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            $errors[$errorKey][] =
                "The {$label} field is required.";
        } elseif (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            $errors[$errorKey][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $item
     * @param  array<string, list<string>>  $errors
     */
    private function nullableItemString(
        array $item,
        string $key,
        string $errorKey,
        int $maxLength,
        string $label,
        array &$errors,
    ): ?string {
        $value =
            $item[$key] ??
            null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $errors[$errorKey][] =
                "The {$label} field must be a string or null.";

            return null;
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) > $maxLength
        ) {
            $errors[$errorKey][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function nullableRating(
        mixed $value,
        string $key,
        array &$errors,
    ): ?int {
        if ($value === null) {
            return null;
        }

        if (
            ! is_int(
                $value,
            ) ||
            $value < 1 ||
            $value > 5
        ) {
            $errors[$key][] =
                'The testimonial rating must be between 1 and 5.';

            return null;
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
                "The selected {$key} value is not supported.";

            return $fallback;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function backgroundColor(
        mixed $value,
        array &$errors,
    ): string {
        if (! is_string($value)) {
            $errors[
                'background_color'
            ][] =
                'The background color is required and must be a string.';

            return '#ffffff';
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
            $errors[
                'background_color'
            ][] =
                'The background color must be a 6-digit hexadecimal color.';

            return '#ffffff';
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function boolean(
        mixed $value,
        string $key,
        array &$errors,
    ): bool {
        if (! is_bool($value)) {
            $errors[$key][] =
                "The {$key} field must be a boolean.";

            return false;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function integerRange(
        mixed $value,
        string $key,
        int $min,
        int $max,
        int $fallback,
        array &$errors,
    ): int {
        if (
            ! is_int(
                $value,
            ) ||
            $value < $min ||
            $value > $max
        ) {
            $errors[$key][] =
                "The {$key} field must be between {$min} and {$max}.";

            return $fallback;
        }

        return $value;
    }

    private function isSafeMediaUrl(
        string $value,
    ): bool {
        $value =
            trim(
                $value,
            );

        if (
            $value === '' ||
            $this->hasAsciiControlCharacter(
                $value,
            )
        ) {
            return false;
        }

        /*
         * Allow an internal application/media path.
         *
         * Example:
         * /storage/page-builder/testimonials/avatar.jpg
         */
        if (
            str_starts_with(
                $value,
                '/',
            )
        ) {
            return
                ! str_starts_with(
                    $value,
                    '//',
                ) &&
                ! str_contains(
                    $value,
                    '\\',
                );
        }

        /*
         * Backslashes are not allowed in
         * absolute media URLs.
         */
        if (
            str_contains(
                $value,
                '\\',
            )
        ) {
            return false;
        }

        $parts =
            parse_url(
                $value,
            );

        if (
            $parts ===
            false
        ) {
            return false;
        }

        $scheme =
            $parts[
                'scheme'
            ] ??
            null;

        if (
            ! is_string(
                $scheme,
            ) ||
            ! in_array(
                strtolower(
                    $scheme,
                ),
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            return false;
        }

        $host =
            $parts[
                'host'
            ] ??
            null;

        if (
            ! is_string(
                $host,
            ) ||
            $host === ''
        ) {
            return false;
        }

        /*
         * Do not allow credentials inside
         * persisted media URLs.
         */
        if (
            isset(
                $parts[
                    'user'
                ],
            ) ||
            isset(
                $parts[
                    'pass'
                ],
            )
        ) {
            return false;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_URL,
        ) !== false;
    }

    private function hasAsciiControlCharacter(
        string $value,
    ): bool {
        $length =
            strlen(
                $value,
            );

        for (
            $index = 0;
            $index <
            $length;
            $index++
        ) {
            $code =
                ord(
                    $value[
                        $index
                    ],
                );

            if (
                $code <= 31 ||
                $code === 127
            ) {
                return true;
            }
        }

        return false;
    }
}
