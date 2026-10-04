<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class ContentConfigSchema implements SectionConfigSchema
{
    private const MAX_HEADING_LENGTH = 160;

    private const MAX_BODY_LENGTH = 5000;

    private const MAX_IMAGE_ALT_LENGTH = 255;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array
    {
        $errors = [];

        $allowedKeys = [
            'heading',
            'body',
            'image',
            'image_alt',
            'alignment',
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

        /*
         * Heading is optional. Laravel converts empty strings to null
         * before request data reaches the domain, so null/missing values
         * normalize back to the canonical empty string.
         */
        $heading =
            $config['heading'] ?? null;

        if ($heading === null) {
            $heading = '';
        } elseif (! is_string($heading)) {
            $errors['heading'][] =
                'The heading field must be a string.';
        } else {
            $heading = trim($heading);

            if (
                mb_strlen($heading) >
                self::MAX_HEADING_LENGTH
            ) {
                $errors['heading'][] =
                    'The heading may not be greater than 160 characters.';
            }
        }

        /*
         * Body is the actual content payload for this section and is
         * deliberately plain text in v1. Arbitrary HTML is not accepted.
         */
        $body =
            $config['body'] ?? null;

        if (! is_string($body)) {
            $errors['body'][] =
                'The body field is required and must be a string.';
        } else {
            $body = trim($body);

            if ($body === '') {
                $errors['body'][] =
                    'The body field is required.';
            } elseif (
                mb_strlen($body) >
                self::MAX_BODY_LENGTH
            ) {
                $errors['body'][] =
                    'The body may not be greater than 5000 characters.';
            }
        }

        $image =
            $config['image'] ?? null;

        if (
            $image !== null &&
            ! is_string($image)
        ) {
            $errors['image'][] =
                'The image field must be a string or null.';
        } elseif (is_string($image)) {
            $image = trim($image);

            if ($image === '') {
                $image = null;
            }
        }

        $imageAlt =
            $config['image_alt'] ?? null;

        if (
            $imageAlt !== null &&
            ! is_string($imageAlt)
        ) {
            $errors['image_alt'][] =
                'The image alt field must be a string or null.';
        } elseif (is_string($imageAlt)) {
            $imageAlt = trim($imageAlt);

            if ($imageAlt === '') {
                $imageAlt = null;
            } elseif (
                mb_strlen($imageAlt) >
                self::MAX_IMAGE_ALT_LENGTH
            ) {
                $errors['image_alt'][] =
                    'The image alt may not be greater than 255 characters.';
            }
        }

        $alignment =
            $config['alignment'] ?? null;

        if (! is_string($alignment)) {
            $errors['alignment'][] =
                'The alignment field is required and must be a string.';
        } elseif (
            ! in_array(
                $alignment,
                [
                    'left',
                    'center',
                    'right',
                ],
                true,
            )
        ) {
            $errors['alignment'][] =
                'The selected alignment is not supported.';
        }

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        assert(is_string($heading));
        assert(is_string($body));

        assert(
            $image === null ||
            is_string($image)
        );

        assert(
            $imageAlt === null ||
            is_string($imageAlt)
        );

        assert(is_string($alignment));

        return [
            'heading' => $heading,
            'body' => $body,
            'image' => $image,
            'image_alt' => $imageAlt,
            'alignment' => $alignment,
        ];
    }
}
