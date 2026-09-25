<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Enums\CatalogSourceType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class FeaturedProductsConfigSchema implements SectionConfigSchema
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
        ];

        foreach (array_keys($config) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
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
            } elseif (mb_strlen($title) > 120) {
                $errors['title'][] =
                    'The title may not be greater than 120 characters.';
            }
        }

        $limit = $config['limit'] ?? null;

        if (! is_int($limit)) {
            $errors['limit'][] =
                'The limit field is required and must be an integer.';
        } elseif ($limit < 1 || $limit > 24) {
            $errors['limit'][] =
                'The limit must be between 1 and 24.';
        }

        $source = $this->validateSource(
            $config['source'] ?? [
                'type' => CatalogSourceType::Featured->value,
            ],
            $errors,
        );

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        /*
         * The checks above guarantee these types before
         * the normalized configuration is returned.
         */
        assert(is_string($title));
        assert(is_int($limit));
        assert(is_array($source));

        return [
            'title' => $title,
            'limit' => $limit,
            'source' => $source,
        ];
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

        $type = $source['type'] ?? null;

        if (! is_string($type)) {
            $errors['source.type'][] =
                'The source type field is required and must be a string.';

            return null;
        }

        $sourceType = CatalogSourceType::tryFrom($type);

        if ($sourceType === null) {
            $errors['source.type'][] =
                'The selected source type is not supported.';

            return null;
        }

        return match ($sourceType) {
            CatalogSourceType::Featured => $this->validateFeaturedSource(
                $source,
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
    private function validateFeaturedSource(
        array $source,
        array &$errors,
    ): array {
        $this->rejectUnknownSourceKeys(
            $source,
            ['type'],
            $errors,
        );

        return [
            'type' => CatalogSourceType::Featured->value,
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
            ['type', 'product_ids'],
            $errors,
        );

        $productIds = $source['product_ids'] ?? null;

        if (! is_array($productIds)) {
            $errors['source.product_ids'][] =
                'The product IDs field is required and must be an array.';

            return [
                'type' => CatalogSourceType::Manual->value,
                'product_ids' => [],
            ];
        }

        if ($productIds === []) {
            $errors['source.product_ids'][] =
                'At least one product must be selected.';
        }

        if (
            count($productIds) >
            self::MAX_MANUAL_PRODUCTS
        ) {
            $errors['source.product_ids'][] =
                'No more than '.self::MAX_MANUAL_PRODUCTS.' products may be selected.';
        }

        $normalizedIds = [];

        foreach ($productIds as $index => $productId) {
            if (! is_int($productId) || $productId < 1) {
                $errors["source.product_ids.{$index}"][] =
                    'Each product ID must be a positive integer.';

                continue;
            }

            $normalizedIds[] = $productId;
        }

        if (
            count($normalizedIds) !==
            count(array_unique($normalizedIds))
        ) {
            $errors['source.product_ids'][] =
                'Product IDs must be unique.';
        }

        return [
            'type' => CatalogSourceType::Manual->value,
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
            ['type', 'category_id'],
            $errors,
        );

        $categoryId =
            $source['category_id'] ?? null;

        if (
            ! is_int($categoryId) ||
            $categoryId < 1
        ) {
            $errors['source.category_id'][] =
                'The category ID field is required and must be a positive integer.';

            return [
                'type' => CatalogSourceType::Category->value,
                'category_id' => null,
            ];
        }

        return [
            'type' => CatalogSourceType::Category->value,
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
        foreach (array_keys($source) as $key) {
            if (
                ! is_string($key) ||
                ! in_array(
                    $key,
                    $allowedKeys,
                    true,
                )
            ) {
                $errorKey = is_string($key)
                    ? "source.{$key}"
                    : 'source';

                $errors[$errorKey][] =
                    'This source configuration field is not supported.';
            }
        }
    }
}
