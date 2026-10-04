<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class ProductCollectionConfigSchema implements SectionConfigSchema
{
    private const MAX_MANUAL_PRODUCTS = 24;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array
    {
        $errors = [];

        $allowedKeys = [
            'title',
            'limit',
            'source',
            'columns',
            'show_price',
            'show_rating',
            'show_badges',
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

        $title =
            $config['title'] ?? null;

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

        $limit =
            $config['limit'] ?? null;

        if (! is_int($limit)) {
            $errors['limit'][] =
                'The limit field is required and must be an integer.';
        } elseif (
            $limit < 1 ||
            $limit > 24
        ) {
            $errors['limit'][] =
                'The limit must be between 1 and 24.';
        }

        $columns =
            $config['columns'] ?? null;

        if (! is_int($columns)) {
            $errors['columns'][] =
                'The columns field is required and must be an integer.';
        } elseif (
            $columns < 1 ||
            $columns > 6
        ) {
            $errors['columns'][] =
                'The columns must be between 1 and 6.';
        }

        $showPrice =
            $config['show_price'] ?? null;

        if (! is_bool($showPrice)) {
            $errors['show_price'][] =
                'The show price field must be true or false.';
        }

        $showRating =
            $config['show_rating'] ?? null;

        if (! is_bool($showRating)) {
            $errors['show_rating'][] =
                'The show rating field must be true or false.';
        }

        $showBadges =
            $config['show_badges'] ?? null;

        if (! is_bool($showBadges)) {
            $errors['show_badges'][] =
                'The show badges field must be true or false.';
        }

        $source = $this->validateSource(
            $config['source'] ?? [
                'type' => CatalogSourceType::Latest
                    ->value,
            ],
            $errors,
        );

        $carouselKeys = [
            'autoplay',
            'autoplay_delay',
            'show_arrows',
            'show_dots',
            'effect',
        ];

        $presentCarouselKeys =
            array_values(
                array_filter(
                    $carouselKeys,
                    static fn (
                        string $key,
                    ): bool => array_key_exists(
                        $key,
                        $config,
                    ),
                ),
            );

        $hasCarouselConfig =
            $presentCarouselKeys !== [];

        if (
            $hasCarouselConfig &&
            count($presentCarouselKeys) !==
                count($carouselKeys)
        ) {
            foreach ($carouselKeys as $key) {
                if (
                    ! array_key_exists(
                        $key,
                        $config,
                    )
                ) {
                    $errors[$key][] =
                        'This field is required for carousel configuration.';
                }
            }
        }

        $autoplay = null;
        $autoplayDelay = null;
        $showArrows = null;
        $showDots = null;
        $effect = null;

        if ($hasCarouselConfig) {
            $autoplay =
                $config['autoplay'] ?? null;

            if (! is_bool($autoplay)) {
                $errors['autoplay'][] =
                    'The autoplay field must be true or false.';
            }

            $autoplayDelay =
                $config[
                    'autoplay_delay'
                ] ?? null;

            if (! is_int($autoplayDelay)) {
                $errors[
                    'autoplay_delay'
                ][] =
                    'The autoplay delay field must be an integer.';
            } elseif (
                $autoplayDelay < 1000 ||
                $autoplayDelay > 30000
            ) {
                $errors[
                    'autoplay_delay'
                ][] =
                    'The autoplay delay must be between 1000 and 30000 milliseconds.';
            }

            $showArrows =
                $config[
                    'show_arrows'
                ] ?? null;

            if (! is_bool($showArrows)) {
                $errors[
                    'show_arrows'
                ][] =
                    'The show arrows field must be true or false.';
            }

            $showDots =
                $config[
                    'show_dots'
                ] ?? null;

            if (! is_bool($showDots)) {
                $errors[
                    'show_dots'
                ][] =
                    'The show dots field must be true or false.';
            }

            $effect =
                $config['effect'] ?? null;

            if (! is_string($effect)) {
                $errors['effect'][] =
                    'The effect field must be a string.';
            } elseif (
                ! in_array(
                    $effect,
                    [
                        'fade',
                        'slide_left',
                        'slide_right',
                        'slide_up',
                        'slide_down',
                    ],
                    true,
                )
            ) {
                $errors['effect'][] =
                    'The selected carousel effect is not supported.';
            }
        }

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        /*
         * Validation above guarantees these
         * values before normalization.
         */
        assert(is_string($title));
        assert(is_int($limit));
        assert(is_int($columns));
        assert(is_bool($showPrice));
        assert(is_bool($showRating));
        assert(is_bool($showBadges));
        assert(is_array($source));

        $normalized = [
            'title' => $title,
            'limit' => $limit,
            'source' => $source,
            'show_price' => $showPrice,
            'show_rating' => $showRating,
            'show_badges' => $showBadges,
            'columns' => $columns,
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
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>|null
     */
    private function validateSource(
        mixed $source,
        array &$errors,
    ): ?array {
        if (! is_array($source)) {
            $errors['source'][] =
                'The source field must be an object.';

            return null;
        }

        $type =
            $source['type'] ?? null;

        if (! is_string($type)) {
            $errors['source.type'][] =
                'The source type field is required and must be a string.';

            return null;
        }

        $sourceType =
            CatalogSourceType::tryFrom(
                $type,
            );

        if ($sourceType === null) {
            $errors['source.type'][] =
                'The selected source type is not supported.';

            return null;
        }

        return match ($sourceType) {
            CatalogSourceType::Latest => $this->validateSimpleSource(
                $source,
                CatalogSourceType::Latest,
                $errors,
            ),

            CatalogSourceType::Featured => $this->validateSimpleSource(
                $source,
                CatalogSourceType::Featured,
                $errors,
            ),

            CatalogSourceType::Manual => $this->validateManualSource(
                $source,
                $errors,
            ),

            CatalogSourceType::Category => $this->validateCategorySource(
                $source,
                $errors,
            ),
        };
    }

    /**
     * @param  array<mixed>  $source
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function validateSimpleSource(
        array $source,
        CatalogSourceType $type,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            $source,
            ['type'],
            $errors,
        );

        return [
            'type' => $type->value,
        ];
    }

    /**
     * @param  array<mixed>  $source
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function validateManualSource(
        array $source,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            $source,
            [
                'type',
                'product_ids',
            ],
            $errors,
        );

        $productIds =
            $source[
                'product_ids'
            ] ?? null;

        if (! is_array($productIds)) {
            $errors[
                'source.product_ids'
            ][] =
                'The product IDs field is required and must be an array.';

            return [
                'type' => CatalogSourceType::Manual
                    ->value,
                'product_ids' => [],
            ];
        }

        if ($productIds === []) {
            $errors[
                'source.product_ids'
            ][] =
                'At least one product must be selected.';
        }

        if (
            count($productIds) >
            self::MAX_MANUAL_PRODUCTS
        ) {
            $errors[
                'source.product_ids'
            ][] =
                'No more than '.
                self::MAX_MANUAL_PRODUCTS.
                ' products may be selected.';
        }

        $normalizedIds = [];

        foreach (
            $productIds as $index => $productId
        ) {
            if (
                ! is_int($productId) ||
                $productId < 1
            ) {
                $errors[
                    "source.product_ids.{$index}"
                ][] =
                    'Each product ID must be a positive integer.';

                continue;
            }

            $normalizedIds[] =
                $productId;
        }

        if (
            count($normalizedIds) !==
            count(
                array_unique(
                    $normalizedIds,
                ),
            )
        ) {
            $errors[
                'source.product_ids'
            ][] =
                'Product IDs must be unique.';
        }

        return [
            'type' => CatalogSourceType::Manual
                ->value,
            'product_ids' => $normalizedIds,
        ];
    }

    /**
     * @param  array<mixed>  $source
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    private function validateCategorySource(
        array $source,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            $source,
            [
                'type',
                'category_id',
            ],
            $errors,
        );

        $categoryId =
            $source[
                'category_id'
            ] ?? null;

        if (
            ! is_int($categoryId) ||
            $categoryId < 1
        ) {
            $errors[
                'source.category_id'
            ][] =
                'The category ID field is required and must be a positive integer.';

            return [
                'type' => CatalogSourceType::Category
                    ->value,
                'category_id' => null,
            ];
        }

        return [
            'type' => CatalogSourceType::Category
                ->value,
            'category_id' => $categoryId,
        ];
    }

    /**
     * @param  array<mixed>  $source
     * @param  list<string>  $allowedKeys
     * @param  array<string, list<string>>  $errors
     */
    private function rejectUnknownSourceKeys(
        array $source,
        array $allowedKeys,
        array &$errors,
    ): void {
        foreach (
            array_keys($source) as $key
        ) {
            if (
                ! is_string($key) ||
                ! in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                $errorKey =
                    is_string($key)
                        ? "source.{$key}"
                        : 'source';

                $errors[$errorKey][] =
                    'This source configuration field is not supported.';
            }
        }
    }
}
