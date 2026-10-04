<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class PromotionalBannerConfigSchema implements SectionConfigSchema
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array
    {
        $errors = [];

        $allowedKeys = [
            'heading',
            'description',
            'image',
            'cta_label',
            'cta_url',
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

        $heading =
            $config['heading'] ?? null;

        if (! is_string($heading)) {
            $errors['heading'][] =
                'The heading field is required and must be a string.';
        } else {
            $heading = trim($heading);

            if ($heading === '') {
                $errors['heading'][] =
                    'The heading field is required.';
            } elseif (
                mb_strlen($heading) > 160
            ) {
                $errors['heading'][] =
                    'The heading may not be greater than 160 characters.';
            }
        }

        /*
         * Laravel's ConvertEmptyStringsToNull middleware converts
         * an empty description submitted from the builder into null.
         *
         * Description is optional for Promotional Banner, so normalize
         * null/missing values back to an empty string.
         */
        $description =
            $config['description'] ?? null;

        if ($description === null) {
            $description = '';
        } elseif (! is_string($description)) {
            $errors['description'][] =
                'The description field must be a string.';
        } else {
            $description =
                trim($description);

            if (
                mb_strlen($description) > 500
            ) {
                $errors['description'][] =
                    'The description may not be greater than 500 characters.';
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

        $ctaLabel =
            $config['cta_label'] ?? null;

        if (
            $ctaLabel !== null &&
            ! is_string($ctaLabel)
        ) {
            $errors['cta_label'][] =
                'The CTA label field must be a string or null.';
        } elseif (is_string($ctaLabel)) {
            $ctaLabel = trim($ctaLabel);

            if ($ctaLabel === '') {
                $ctaLabel = null;
            } elseif (
                mb_strlen($ctaLabel) > 80
            ) {
                $errors['cta_label'][] =
                    'The CTA label may not be greater than 80 characters.';
            }
        }

        $ctaUrl =
            $config['cta_url'] ?? null;

        if (
            $ctaUrl !== null &&
            ! is_string($ctaUrl)
        ) {
            $errors['cta_url'][] =
                'The CTA URL field must be a string or null.';
        } elseif (is_string($ctaUrl)) {
            $ctaUrl = trim($ctaUrl);

            if ($ctaUrl === '') {
                $ctaUrl = null;
            } elseif (
                mb_strlen($ctaUrl) > 2048
            ) {
                $errors['cta_url'][] =
                    'The CTA URL may not be greater than 2048 characters.';
            }
        }

        if (
            ($ctaLabel === null) !==
            ($ctaUrl === null)
        ) {
            $errors['cta_label'][] =
                'The CTA label and CTA URL must be provided together.';
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
        assert(is_string($description));

        assert(
            $image === null ||
            is_string($image)
        );

        assert(
            $ctaLabel === null ||
            is_string($ctaLabel)
        );

        assert(
            $ctaUrl === null ||
            is_string($ctaUrl)
        );

        assert(is_string($alignment));

        return [
            'heading' => $heading,
            'description' => $description,
            'image' => $image,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
            'alignment' => $alignment,
        ];
    }
}
