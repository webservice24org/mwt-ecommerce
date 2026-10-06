<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class FeaturesBenefitsConfigSchema implements SectionConfigSchema
{
    private const MAX_EYEBROW_LENGTH = 120;

    private const MAX_HEADING_LENGTH = 180;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_ITEMS = 12;

    private const MAX_ITEM_TITLE_LENGTH = 160;

    private const MAX_ITEM_DESCRIPTION_LENGTH = 1000;

    private const MAX_ICON_LENGTH = 80;

    private const MAX_IMAGE_LENGTH = 2048;

    private const MAX_IMAGE_ALT_LENGTH = 255;

    private const MAX_LINK_LABEL_LENGTH = 80;

    private const MAX_LINK_URL_LENGTH = 2048;

    private const ALLOWED_ICONS = [
        'truck',
        'shield-check',
        'headphones',
        'badge-check',
        'award',
        'package-check',
        'refresh-ccw',
        'credit-card',
        'lock',
        'clock',
        'gift',
        'heart-handshake',
        'shopping-bag',
        'store',
        'box',
        'star',
        'sparkles',
        'leaf',
        'zap',
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
            'columns',
            'alignment',
            'background_color',
            'text_theme',
        ];

        foreach (
            array_keys($config) as $key
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
            $this->requiredString(
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

        $columns =
            $this->columns(
                value: $config['columns'] ??
                    null,
                errors: $errors,
            );

        $alignment =
            $this->alignment(
                value: $config['alignment'] ??
                    null,
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
            $this->textTheme(
                value: $config['text_theme'] ??
                    null,
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

            'columns' => $columns,

            'alignment' => $alignment,

            'background_color' => $backgroundColor,

            'text_theme' => $textTheme,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function requiredString(
        array $config,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): string {
        $value =
            $config[$key] ??
            null;

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field is required and must be a string.";

            return '';
        }

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            $errors[$key][] =
                "The {$label} field is required.";
        } elseif (
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
            null;

        if ($value === null) {
            return '';
        }

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
     * @param  array<string, list<string>>  $errors
     * @return list<array<string, mixed>>
     */
    private function items(
        mixed $value,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $errors['items'][] =
                'The items field is required and must be an array.';

            return [];
        }

        if (! array_is_list($value)) {
            $errors['items'][] =
                'The items field must be a list.';

            return [];
        }

        $count =
            count(
                $value,
            );

        if ($count < 1) {
            $errors['items'][] =
                'At least one feature or benefit item is required.';
        } elseif (
            $count >
            self::MAX_ITEMS
        ) {
            $errors['items'][] =
                'The items field may not contain more than 12 items.';
        }

        $normalized = [];

        foreach (
            $value as $index => $item
        ) {
            if (! is_array($item)) {
                $errors[
                    "items.{$index}"
                ][] =
                    'Each feature or benefit item must be an object.';

                continue;
            }

            $normalized[] =
                $this->normalizeItem(
                    item: $item,
                    index: $index,
                    errors: $errors,
                );
        }

        return $normalized;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function normalizeItem(
        array $item,
        int $index,
        array &$errors,
    ): array {
        $allowedKeys = [
            'title',
            'description',
            'icon',
            'image',
            'image_alt',
            'link_label',
            'link_url',
        ];

        foreach (
            array_keys($item) as $key
        ) {
            if (
                ! is_string($key) ||
                ! in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                $errors[
                    "items.{$index}.{$key}"
                ][] =
                    'This item configuration field is not supported.';
            }
        }

        $title =
            $this->itemRequiredString(
                item: $item,
                index: $index,
                key: 'title',
                maxLength: self::MAX_ITEM_TITLE_LENGTH,
                label: 'title',
                errors: $errors,
            );

        $description =
            $this->itemOptionalString(
                item: $item,
                index: $index,
                key: 'description',
                maxLength: self::MAX_ITEM_DESCRIPTION_LENGTH,
                label: 'description',
                errors: $errors,
            );

        $icon =
            $this->itemNullableString(
                item: $item,
                index: $index,
                key: 'icon',
                maxLength: self::MAX_ICON_LENGTH,
                label: 'icon',
                errors: $errors,
            );

        if (
            $icon !== null &&
            ! in_array(
                $icon,
                self::ALLOWED_ICONS,
                true,
            )
        ) {
            $errors[
                "items.{$index}.icon"
            ][] =
                'The selected feature icon is not supported.';
        }

        $image =
            $this->itemNullableString(
                item: $item,
                index: $index,
                key: 'image',
                maxLength: self::MAX_IMAGE_LENGTH,
                label: 'image',
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
                'The image must use a safe internal path or an HTTP/HTTPS URL.';
        }

        $imageAlt =
            $this->itemNullableString(
                item: $item,
                index: $index,
                key: 'image_alt',
                maxLength: self::MAX_IMAGE_ALT_LENGTH,
                label: 'image alt',
                errors: $errors,
            );

        $linkLabel =
            $this->itemNullableString(
                item: $item,
                index: $index,
                key: 'link_label',
                maxLength: self::MAX_LINK_LABEL_LENGTH,
                label: 'link label',
                errors: $errors,
            );

        $linkUrl =
            $this->itemNullableString(
                item: $item,
                index: $index,
                key: 'link_url',
                maxLength: self::MAX_LINK_URL_LENGTH,
                label: 'link URL',
                errors: $errors,
            );

        if (
            $linkLabel !== null &&
            $linkUrl === null
        ) {
            $errors[
                "items.{$index}.link_url"
            ][] =
                'The link URL is required when a link label is provided.';
        }

        if (
            $linkUrl !== null &&
            $linkLabel === null
        ) {
            $errors[
                "items.{$index}.link_label"
            ][] =
                'The link label is required when a link URL is provided.';
        }

        if (
            $linkUrl !== null &&
            ! $this->isSafeLinkUrl(
                $linkUrl,
            )
        ) {
            $errors[
                "items.{$index}.link_url"
            ][] =
                'The link URL must use a safe internal path, section anchor, or HTTP/HTTPS URL.';
        }

        return [
            'title' => $title,

            'description' => $description,

            'icon' => $icon,

            'image' => $image,

            'image_alt' => $imageAlt,

            'link_label' => $linkLabel,

            'link_url' => $linkUrl,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<string, list<string>>  $errors
     */
    private function itemRequiredString(
        array $item,
        int $index,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): string {
        $errorKey =
            "items.{$index}.{$key}";

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
     * @param  array<array-key, mixed>  $item
     * @param  array<string, list<string>>  $errors
     */
    private function itemOptionalString(
        array $item,
        int $index,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): string {
        $errorKey =
            "items.{$index}.{$key}";

        $value =
            $item[$key] ??
            null;

        if ($value === null) {
            return '';
        }

        if (! is_string($value)) {
            $errors[$errorKey][] =
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
            $errors[$errorKey][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<string, list<string>>  $errors
     */
    private function itemNullableString(
        array $item,
        int $index,
        string $key,
        int $maxLength,
        string $label,
        array &$errors,
    ): ?string {
        $errorKey =
            "items.{$index}.{$key}";

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
    private function columns(
        mixed $value,
        array &$errors,
    ): int {
        if (
            ! is_int($value) ||
            ! in_array(
                $value,
                [
                    2,
                    3,
                    4,
                ],
                true,
            )
        ) {
            $errors['columns'][] =
                'The columns field must be 2, 3, or 4.';

            return 3;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function alignment(
        mixed $value,
        array &$errors,
    ): string {
        if (
            ! is_string($value) ||
            ! in_array(
                $value,
                [
                    'left',
                    'center',
                ],
                true,
            )
        ) {
            $errors['alignment'][] =
                'The selected alignment is not supported.';

            return 'left';
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
            $errors['background_color'][] =
                'The background color field is required and must be a string.';

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
            $errors['background_color'][] =
                'The background color must be a 6-digit hexadecimal color.';

            return '#ffffff';
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function textTheme(
        mixed $value,
        array &$errors,
    ): string {
        if (
            ! is_string($value) ||
            ! in_array(
                $value,
                [
                    'light',
                    'dark',
                ],
                true,
            )
        ) {
            $errors['text_theme'][] =
                'The selected text theme is not supported.';

            return 'dark';
        }

        return $value;
    }

    /**
     * Links may use:
     *
     * - root-relative internal paths
     * - same-page fragment anchors
     * - absolute HTTP/HTTPS URLs
     */
    private function isSafeLinkUrl(
        string $value,
    ): bool {
        $value =
            trim(
                $value,
            );

        if (
            $value === '' ||
            $this->hasUnsafeUrlCharacters(
                $value,
            )
        ) {
            return false;
        }

        if (
            str_starts_with(
                $value,
                '#',
            )
        ) {
            return strlen(
                $value,
            ) > 1;
        }

        return $this->isSafeWebUrl(
            $value,
        );
    }

    /**
     * Media values may use:
     *
     * - root-relative internal paths
     * - absolute HTTP/HTTPS URLs
     *
     * Fragment-only values are not valid media URLs.
     */
    private function isSafeMediaUrl(
        string $value,
    ): bool {
        $value =
            trim(
                $value,
            );

        if (
            $value === '' ||
            $this->hasUnsafeUrlCharacters(
                $value,
            )
        ) {
            return false;
        }

        return $this->isSafeWebUrl(
            $value,
        );
    }

    /**
     * Allow internal root-relative URLs or absolute
     * HTTP/HTTPS URLs without embedded credentials.
     */
    private function isSafeWebUrl(
        string $value,
    ): bool {
        /*
         * Internal application path.
         *
         * A value beginning with // is protocol-relative
         * and therefore deliberately rejected.
         */
        if (
            str_starts_with(
                $value,
                '/',
            )
        ) {
            return ! str_starts_with(
                $value,
                '//',
            );
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return false;
        }

        $parts =
            parse_url(
                $value,
            );

        if (
            ! is_array(
                $parts,
            ) ||
            ! isset(
                $parts['scheme'],
                $parts['host'],
            )
        ) {
            return false;
        }

        if (
            ! in_array(
                strtolower(
                    $parts['scheme'],
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

        /*
         * Embedded URL credentials are unnecessary
         * for Page Builder content and could expose
         * sensitive data, so reject them.
         */
        if (
            isset(
                $parts['user'],
            ) ||
            isset(
                $parts['pass'],
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Reject raw whitespace, ASCII control characters,
     * DEL, and backslashes inside URLs.
     */
    private function hasUnsafeUrlCharacters(
        string $value,
    ): bool {
        if (
            str_contains(
                $value,
                '\\',
            )
        ) {
            return true;
        }

        return preg_match(
            '/[\x00-\x20\x7F]/',
            $value,
        ) === 1;
    }
}
