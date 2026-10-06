<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Enums\BrandSourceType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class BrandConfigSchema implements SectionConfigSchema
{
    private const MAX_EYEBROW_LENGTH = 120;

    private const MAX_HEADING_LENGTH = 180;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_LABEL_LENGTH = 100;

    private const MAX_URL_LENGTH = 2048;

    private const MAX_BRANDS = 24;

    private const MIN_MARQUEE_DURATION = 10;

    private const MAX_MARQUEE_DURATION = 60;

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

            'source',

            'limit',
            'columns',
            'alignment',

            'background_color',
            'text_theme',

            'show_name',
            'show_description',
            'show_product_count',

            'view_all_label',
            'view_all_url',

            'primary_button_label',
            'primary_button_url',

            'secondary_button_label',
            'secondary_button_url',

            'marquee_duration',
            'pause_on_hover',
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

        $source =
            $this->source(
                value: $config['source'] ??
                null,
                errors: $errors,
            );

        $limit =
            $this->limit(
                value: $config['limit'] ??
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

        $showName =
            $this->boolean(
                value: $config['show_name'] ??
                null,
                key: 'show_name',
                errors: $errors,
            );

        $showDescription =
            $this->boolean(
                value: $config[
                    'show_description'
                ] ?? null,
                key: 'show_description',
                errors: $errors,
            );

        $showProductCount =
            $this->boolean(
                value: $config[
                    'show_product_count'
                ] ?? null,
                key: 'show_product_count',
                errors: $errors,
            );

        $viewAllLabel =
            $this->nullableString(
                config: $config,
                key: 'view_all_label',
                maxLength: self::MAX_LABEL_LENGTH,
                label: 'view all label',
                errors: $errors,
            );

        $viewAllUrl =
            $this->nullableUrl(
                config: $config,
                key: 'view_all_url',
                label: 'view all URL',
                errors: $errors,
            );

        $primaryButtonLabel =
            $this->nullableString(
                config: $config,
                key: 'primary_button_label',
                maxLength: self::MAX_LABEL_LENGTH,
                label: 'primary button label',
                errors: $errors,
            );

        $primaryButtonUrl =
            $this->nullableUrl(
                config: $config,
                key: 'primary_button_url',
                label: 'primary button URL',
                errors: $errors,
            );

        $secondaryButtonLabel =
            $this->nullableString(
                config: $config,
                key: 'secondary_button_label',
                maxLength: self::MAX_LABEL_LENGTH,
                label: 'secondary button label',
                errors: $errors,
            );

        $secondaryButtonUrl =
            $this->nullableUrl(
                config: $config,
                key: 'secondary_button_url',
                label: 'secondary button URL',
                errors: $errors,
            );

        $this->validateLinkPair(
            label: $viewAllLabel,
            url: $viewAllUrl,
            labelKey: 'view_all_label',
            urlKey: 'view_all_url',
            errors: $errors,
        );

        $this->validateLinkPair(
            label: $primaryButtonLabel,
            url: $primaryButtonUrl,
            labelKey: 'primary_button_label',
            urlKey: 'primary_button_url',
            errors: $errors,
        );

        $this->validateLinkPair(
            label: $secondaryButtonLabel,
            url: $secondaryButtonUrl,
            labelKey: 'secondary_button_label',
            urlKey: 'secondary_button_url',
            errors: $errors,
        );

        $marqueeDuration =
            $this->marqueeDuration(
                value: $config[
                    'marquee_duration'
                ] ?? null,
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

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        return [
            'eyebrow' => $eyebrow,

            'heading' => $heading,

            'description' => $description,

            'source' => $source,

            'limit' => $limit,

            'columns' => $columns,

            'alignment' => $alignment,

            'background_color' => $backgroundColor,

            'text_theme' => $textTheme,

            'show_name' => $showName,

            'show_description' => $showDescription,

            'show_product_count' => $showProductCount,

            'view_all_label' => $viewAllLabel,

            'view_all_url' => $viewAllUrl,

            'primary_button_label' => $primaryButtonLabel,

            'primary_button_url' => $primaryButtonUrl,

            'secondary_button_label' => $secondaryButtonLabel,

            'secondary_button_url' => $secondaryButtonUrl,

            'marquee_duration' => $marqueeDuration,

            'pause_on_hover' => $pauseOnHover,
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
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function nullableUrl(
        array $config,
        string $key,
        string $label,
        array &$errors,
    ): ?string {
        $url =
            $this->nullableString(
                config: $config,
                key: $key,
                maxLength: self::MAX_URL_LENGTH,
                label: $label,
                errors: $errors,
            );

        if ($url === null) {
            return null;
        }

        if (
            ! $this->isSafeUrl(
                $url,
            )
        ) {
            $errors[$key][] =
                "The {$label} must be a safe internal, HTTP, or HTTPS URL.";
        }

        return $url;
    }

    private function isSafeUrl(
        string $url,
    ): bool {
        /*
         * Reject ASCII control characters.
         *
         * They can be used to create confusing
         * or differently interpreted URLs.
         */
        if (
            preg_match(
                '/[\x00-\x1f\x7f]/',
                $url,
            ) === 1
        ) {
            return false;
        }

        /*
         * Internal application URL.
         *
         * Allow:
         *   /products
         *   /products?brand=nike
         *   /page#section
         *
         * Reject:
         *   //evil.example
         *   /\evil.example
         */
        if (
            str_starts_with(
                $url,
                '/',
            )
        ) {
            return
                ! str_starts_with(
                    $url,
                    '//',
                ) &&
                ! str_contains(
                    $url,
                    '\\',
                );
        }

        /*
         * External URLs are restricted to
         * HTTP and HTTPS.
         */
        if (
            filter_var(
                $url,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return false;
        }

        $scheme =
            parse_url(
                $url,
                PHP_URL_SCHEME,
            );

        if (
            ! is_string(
                $scheme,
            )
        ) {
            return false;
        }

        return in_array(
            strtolower(
                $scheme,
            ),
            [
                'http',
                'https',
            ],
            true,
        );
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function source(
        mixed $value,
        array &$errors,
    ): array {
        if (! is_array($value)) {
            $errors['source'][] =
                'The source field is required and must be an object.';

            return [
                'type' => BrandSourceType::All->value,
            ];
        }

        $typeValue =
            $value['type'] ??
            null;

        if (! is_string($typeValue)) {
            $errors['source.type'][] =
                'The source type field is required.';

            return [
                'type' => BrandSourceType::All->value,
            ];
        }

        $type =
            BrandSourceType::tryFrom(
                $typeValue,
            );

        if ($type === null) {
            $errors['source.type'][] =
                'The selected brand source type is not supported.';

            return [
                'type' => BrandSourceType::All->value,
            ];
        }

        return match ($type) {
            BrandSourceType::All => $this->allSource(
                source: $value,
                errors: $errors,
            ),

            BrandSourceType::Manual => $this->manualSource(
                source: $value,
                errors: $errors,
            ),
        };
    }

    /**
     * @param  array<mixed>  $source
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function allSource(
        array $source,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            source: $source,
            allowed: [
                'type',
            ],
            errors: $errors,
        );

        return [
            'type' => BrandSourceType::All->value,
        ];
    }

    /**
     * @param  array<mixed>  $source
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function manualSource(
        array $source,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            source: $source,
            allowed: [
                'type',
                'brand_ids',
            ],
            errors: $errors,
        );

        $brandIds =
            $source[
                'brand_ids'
            ] ?? null;

        if (! is_array($brandIds)) {
            $errors[
                'source.brand_ids'
            ][] =
                'The brand IDs field is required and must be an array.';

            return [
                'type' => BrandSourceType::Manual->value,

                'brand_ids' => [],
            ];
        }

        if (! array_is_list($brandIds)) {
            $errors[
                'source.brand_ids'
            ][] =
                'The brand IDs field must be a list.';

            return [
                'type' => BrandSourceType::Manual->value,

                'brand_ids' => [],
            ];
        }

        if ($brandIds === []) {
            $errors[
                'source.brand_ids'
            ][] =
                'At least one brand must be selected.';
        }

        if (
            count(
                $brandIds,
            ) > self::MAX_BRANDS
        ) {
            $errors[
                'source.brand_ids'
            ][] =
                'No more than 24 brands may be selected.';
        }

        $normalized = [];

        foreach (
            $brandIds as $index => $brandId
        ) {
            if (
                ! is_int($brandId) ||
                $brandId < 1
            ) {
                $errors[
                    "source.brand_ids.{$index}"
                ][] =
                    'Each brand ID must be a positive integer.';

                continue;
            }

            $normalized[] =
                $brandId;
        }

        if (
            count(
                $normalized,
            ) !==
            count(
                array_unique(
                    $normalized,
                ),
            )
        ) {
            $errors[
                'source.brand_ids'
            ][] =
                'Brand IDs must be unique.';
        }

        return [
            'type' => BrandSourceType::Manual->value,

            'brand_ids' => $normalized,
        ];
    }

    /**
     * @param  array<mixed>  $source
     * @param  list<string>  $allowed
     * @param  array<string, list<string>>  $errors
     */
    private function rejectUnknownSourceKeys(
        array $source,
        array $allowed,
        array &$errors,
    ): void {
        foreach (
            array_keys(
                $source,
            ) as $key
        ) {
            if (
                ! is_string($key) ||
                ! in_array(
                    $key,
                    $allowed,
                    true,
                )
            ) {
                $errors[
                    "source.{$key}"
                ][] =
                    'This source configuration field is not supported.';
            }
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function limit(
        mixed $value,
        array &$errors,
    ): int {
        if (
            ! is_int($value) ||
            $value < 1 ||
            $value >
            self::MAX_BRANDS
        ) {
            $errors['limit'][] =
                'The limit must be between 1 and 24.';

            return 12;
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
                    5,
                    6,
                ],
                true,
            )
        ) {
            $errors['columns'][] =
                'The columns field must be between 2 and 6.';

            return 4;
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

            return 'center';
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
            $errors[
                'text_theme'
            ][] =
                'The selected text theme is not supported.';

            return 'dark';
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
    private function marqueeDuration(
        mixed $value,
        array &$errors,
    ): int {
        if (
            ! is_int($value) ||
            $value <
            self::MIN_MARQUEE_DURATION ||
            $value >
            self::MAX_MARQUEE_DURATION
        ) {
            $errors[
                'marquee_duration'
            ][] =
                'The marquee duration must be between 10 and 60 seconds.';

            return 25;
        }

        return $value;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function validateLinkPair(
        ?string $label,
        ?string $url,
        string $labelKey,
        string $urlKey,
        array &$errors,
    ): void {
        if (
            $label !== null &&
            $url === null
        ) {
            $errors[$urlKey][] =
                'A URL is required when a label is provided.';
        }

        if (
            $url !== null &&
            $label === null
        ) {
            $errors[$labelKey][] =
                'A label is required when a URL is provided.';
        }
    }
}
