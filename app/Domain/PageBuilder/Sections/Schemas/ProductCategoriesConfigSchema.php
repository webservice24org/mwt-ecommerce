<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class ProductCategoriesConfigSchema implements SectionConfigSchema
{
    private const MIN_COLUMNS = 2;

    private const MAX_COLUMNS = 6;

    private const MIN_AUTOPLAY_DELAY = 1000;

    private const MAX_AUTOPLAY_DELAY = 30000;

    private const CAROUSEL_EFFECTS = [
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
    public function validate(array $config): array
    {
        $errors = [];

        $allowedKeys = [
            'title',
            'category_ids',
            'columns',
            'show_name',
            'show_product_count',
            'autoplay',
            'autoplay_delay',
            'show_arrows',
            'show_dots',
            'effect',
        ];

        foreach (array_keys($config) as $key) {
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

        $title = $config['title'] ?? null;

        if (! is_string($title)) {
            $errors['title'][] =
                'The title field is required and must be a string.';
        } else {
            $title = trim($title);

            if ($title === '') {
                $errors['title'][] =
                    'The title field is required.';
            } elseif (
                mb_strlen($title) > 120
            ) {
                $errors['title'][] =
                    'The title may not be greater than 120 characters.';
            }
        }

        $categoryIds =
            $config['category_ids'] ?? null;

        $normalizedCategoryIds = [];

        if (! is_array($categoryIds)) {
            $errors['category_ids'][] =
                'The category IDs field is required and must be an array.';
        } else {
            foreach (
                $categoryIds as $index => $categoryId
            ) {
                if (
                    ! is_int($categoryId) ||
                    $categoryId < 1
                ) {
                    $errors[
                        "category_ids.{$index}"
                    ][] =
                        'Each category ID must be a positive integer.';

                    continue;
                }

                $normalizedCategoryIds[] =
                    $categoryId;
            }

            if (
                count($normalizedCategoryIds) !==
                count(
                    array_unique(
                        $normalizedCategoryIds,
                    ),
                )
            ) {
                $errors['category_ids'][] =
                    'Category IDs must be unique.';
            }
        }

        $columns = $config['columns'] ?? null;

        if (! is_int($columns)) {
            $errors['columns'][] =
                'The columns field is required and must be an integer.';
        } elseif (
            $columns < self::MIN_COLUMNS ||
            $columns > self::MAX_COLUMNS
        ) {
            $errors['columns'][] =
                'The columns field must be between 2 and 6.';
        }

        $showName =
            $this->validateRequiredBoolean(
                $config,
                'show_name',
                $errors,
            );

        $showProductCount =
            $this->validateRequiredBoolean(
                $config,
                'show_product_count',
                $errors,
            );

        $hasCarouselConfig =
            array_key_exists(
                'autoplay',
                $config,
            ) ||
            array_key_exists(
                'autoplay_delay',
                $config,
            ) ||
            array_key_exists(
                'show_arrows',
                $config,
            ) ||
            array_key_exists(
                'show_dots',
                $config,
            ) ||
            array_key_exists(
                'effect',
                $config,
            );

        $autoplay = null;
        $autoplayDelay = null;
        $showArrows = null;
        $showDots = null;
        $effect = null;

        if ($hasCarouselConfig) {
            $autoplay =
                $this->validateRequiredBoolean(
                    $config,
                    'autoplay',
                    $errors,
                );

            $autoplayDelay =
                $config[
                    'autoplay_delay'
                ] ?? null;

            if (! is_int($autoplayDelay)) {
                $errors[
                    'autoplay_delay'
                ][] =
                    'The autoplay delay field is required and must be an integer.';
            } elseif (
                $autoplayDelay <
                    self::MIN_AUTOPLAY_DELAY ||
                $autoplayDelay >
                    self::MAX_AUTOPLAY_DELAY
            ) {
                $errors[
                    'autoplay_delay'
                ][] =
                    'The autoplay delay must be between 1000 and 30000 milliseconds.';
            }

            $showArrows =
                $this->validateRequiredBoolean(
                    $config,
                    'show_arrows',
                    $errors,
                );

            $showDots =
                $this->validateRequiredBoolean(
                    $config,
                    'show_dots',
                    $errors,
                );

            $effect =
                $config['effect'] ?? null;

            if (! is_string($effect)) {
                $errors['effect'][] =
                    'The carousel effect field is required and must be a string.';
            } elseif (
                ! in_array(
                    $effect,
                    self::CAROUSEL_EFFECTS,
                    true,
                )
            ) {
                $errors['effect'][] =
                    'The carousel effect is invalid.';
            }
        }

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        assert(is_string($title));
        assert(is_int($columns));
        assert(is_bool($showName));
        assert(is_bool($showProductCount));
        $normalized = [
            'title' => $title,
            'category_ids' => $normalizedCategoryIds,
            'show_name' => $showName,
            'columns' => $columns,
            'show_product_count' => $showProductCount,
        ];

        if ($hasCarouselConfig) {
            assert(is_bool($autoplay));
            assert(is_int($autoplayDelay));
            assert(is_bool($showArrows));
            assert(is_bool($showDots));
            assert(is_string($effect));

            $normalized['autoplay'] =
                $autoplay;

            $normalized[
                'autoplay_delay'
            ] = $autoplayDelay;

            $normalized[
                'show_arrows'
            ] = $showArrows;

            $normalized[
                'show_dots'
            ] = $showDots;

            $normalized['effect'] =
                 $effect;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function validateRequiredBoolean(
        array $config,
        string $key,
        array &$errors,
    ): ?bool {
        $value = $config[$key] ?? null;

        if (! is_bool($value)) {
            $errors[$key][] =
                sprintf(
                    'The %s field is required and must be a boolean.',
                    str_replace(
                        '_',
                        ' ',
                        $key,
                    ),
                );

            return null;
        }

        return $value;
    }
}
