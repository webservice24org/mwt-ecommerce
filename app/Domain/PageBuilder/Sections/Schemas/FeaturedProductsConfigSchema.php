<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class FeaturedProductsConfigSchema implements SectionConfigSchema
{
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

        return [
            'title' => $title,
            'limit' => $limit,
        ];
    }
}
