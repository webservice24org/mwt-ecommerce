<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Schemas;

use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final class CallToActionConfigSchema implements SectionConfigSchema
{
    private const MAX_EYEBROW_LENGTH = 120;

    private const MAX_HEADING_LENGTH = 180;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    private const MAX_LABEL_LENGTH = 80;

    private const MAX_CONTACT_LENGTH = 80;

    private const MAX_EMAIL_LENGTH = 254;

    private const MAX_BACKGROUND_IMAGE_LENGTH = 2048;

    private const MAX_WHATSAPP_MESSAGE_LENGTH = 500;

    private const MAX_NEWSLETTER_PLACEHOLDER_LENGTH = 160;

    private const MAX_NEWSLETTER_NOTE_LENGTH = 255;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array
    {
        /** @var array<string, list<string>> $errors */
        $errors = [];

        $allowedKeys = [
            'eyebrow',
            'heading',
            'description',
            'background_type',
            'background_color',
            'background_image',
            'background_overlay',
            'text_theme',
            'phone_label',
            'phone_number',
            'email_label',
            'email',
            'whatsapp_label',
            'whatsapp_number',
            'whatsapp_message',
            'newsletter_placeholder',
            'newsletter_button_label',
            'newsletter_note',
        ];

        foreach (array_keys($config) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                $errors[$key][] =
                    'This configuration field is not supported.';
            }
        }

        $eyebrow = $this->nullableString(
            config: $config,
            key: 'eyebrow',
            maxLength: self::MAX_EYEBROW_LENGTH,
            label: 'eyebrow',
            errors: $errors,
        );

        $heading = $this->requiredString(
            config: $config,
            key: 'heading',
            maxLength: self::MAX_HEADING_LENGTH,
            label: 'heading',
            errors: $errors,
        );

        $description = $this->optionalString(
            config: $config,
            key: 'description',
            maxLength: self::MAX_DESCRIPTION_LENGTH,
            label: 'description',
            errors: $errors,
        );

        $backgroundType =
            $config['background_type'] ?? null;

        if (! is_string($backgroundType)) {
            $errors['background_type'][] =
                'The background type field is required and must be a string.';
        } elseif (
            ! in_array(
                $backgroundType,
                [
                    'color',
                    'image',
                ],
                true,
            )
        ) {
            $errors['background_type'][] =
                'The selected background type is not supported.';
        }

        $backgroundColor =
            $config['background_color'] ?? null;

        if (! is_string($backgroundColor)) {
            $errors['background_color'][] =
                'The background color field is required and must be a string.';
        } else {
            $backgroundColor =
                strtolower(
                    trim(
                        $backgroundColor,
                    ),
                );

            if (
                preg_match(
                    '/^#[0-9a-f]{6}$/',
                    $backgroundColor,
                ) !== 1
            ) {
                $errors['background_color'][] =
                    'The background color must be a 6-digit hexadecimal color.';
            }
        }

        $backgroundImage = $this->nullableString(
            config: $config,
            key: 'background_image',
            maxLength: self::MAX_BACKGROUND_IMAGE_LENGTH,
            label: 'background image',
            errors: $errors,
        );

        if (
            $backgroundType === 'image' &&
            $backgroundImage === null
        ) {
            $errors['background_image'][] =
                'The background image field is required when the background type is image.';
        }

        if (
            $backgroundImage !== null &&
            ! $this->isSafeBackgroundImageReference(
                $backgroundImage,
            )
        ) {
            $errors['background_image'][] =
                'The background image must be an application-relative path or an HTTP/HTTPS URL.';
        }

        $backgroundOverlay =
            $config['background_overlay'] ?? null;

        if (! is_int($backgroundOverlay)) {
            $errors['background_overlay'][] =
                'The background overlay field is required and must be an integer.';
        } elseif (
            $backgroundOverlay < 0 ||
            $backgroundOverlay > 100
        ) {
            $errors['background_overlay'][] =
                'The background overlay must be between 0 and 100.';
        }

        $textTheme =
            $config['text_theme'] ?? null;

        if (! is_string($textTheme)) {
            $errors['text_theme'][] =
                'The text theme field is required and must be a string.';
        } elseif (
            ! in_array(
                $textTheme,
                [
                    'light',
                    'dark',
                ],
                true,
            )
        ) {
            $errors['text_theme'][] =
                'The selected text theme is not supported.';
        }

        $phoneLabel = $this->nullableString(
            config: $config,
            key: 'phone_label',
            maxLength: self::MAX_LABEL_LENGTH,
            label: 'phone label',
            errors: $errors,
        );

        $phoneNumber = $this->contactNumber(
            config: $config,
            key: 'phone_number',
            label: 'phone number',
            errors: $errors,
        );

        $emailLabel = $this->nullableString(
            config: $config,
            key: 'email_label',
            maxLength: self::MAX_LABEL_LENGTH,
            label: 'email label',
            errors: $errors,
        );

        $email = $this->nullableString(
            config: $config,
            key: 'email',
            maxLength: self::MAX_EMAIL_LENGTH,
            label: 'email',
            errors: $errors,
        );

        if (
            $email !== null &&
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            $errors['email'][] =
                'The email field must contain a valid email address.';
        }

        $whatsappLabel = $this->nullableString(
            config: $config,
            key: 'whatsapp_label',
            maxLength: self::MAX_LABEL_LENGTH,
            label: 'WhatsApp label',
            errors: $errors,
        );

        $whatsappNumber = $this->contactNumber(
            config: $config,
            key: 'whatsapp_number',
            label: 'WhatsApp number',
            errors: $errors,
        );

        $whatsappMessage = $this->nullableString(
            config: $config,
            key: 'whatsapp_message',
            maxLength: self::MAX_WHATSAPP_MESSAGE_LENGTH,
            label: 'WhatsApp message',
            errors: $errors,
        );

        $newsletterPlaceholder = $this->nullableString(
            config: $config,
            key: 'newsletter_placeholder',
            maxLength: self::MAX_NEWSLETTER_PLACEHOLDER_LENGTH,
            label: 'newsletter placeholder',
            errors: $errors,
        );

        $newsletterButtonLabel = $this->nullableString(
            config: $config,
            key: 'newsletter_button_label',
            maxLength: self::MAX_LABEL_LENGTH,
            label: 'newsletter button label',
            errors: $errors,
        );

        $newsletterNote = $this->nullableString(
            config: $config,
            key: 'newsletter_note',
            maxLength: self::MAX_NEWSLETTER_NOTE_LENGTH,
            label: 'newsletter note',
            errors: $errors,
        );

        if ($errors !== []) {
            throw new InvalidSectionConfiguration(
                $errors,
            );
        }

        assert(is_string($backgroundType));
        assert(is_string($backgroundColor));
        assert(is_int($backgroundOverlay));
        assert(is_string($textTheme));

        return [
            'eyebrow' => $eyebrow,
            'heading' => $heading,
            'description' => $description,
            'background_type' => $backgroundType,
            'background_color' => $backgroundColor,
            'background_image' => $backgroundImage,
            'background_overlay' => $backgroundOverlay,
            'text_theme' => $textTheme,
            'phone_label' => $phoneLabel,
            'phone_number' => $phoneNumber,
            'email_label' => $emailLabel,
            'email' => $email,
            'whatsapp_label' => $whatsappLabel,
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_message' => $whatsappMessage,
            'newsletter_placeholder' => $newsletterPlaceholder,
            'newsletter_button_label' => $newsletterButtonLabel,
            'newsletter_note' => $newsletterNote,
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
            $config[$key] ?? null;

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field is required and must be a string.";

            return '';
        }

        $value = trim($value);

        if ($value === '') {
            $errors[$key][] =
                "The {$label} field is required.";
        } elseif (
            mb_strlen($value) >
            $maxLength
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
            $config[$key] ?? null;

        if ($value === null) {
            return '';
        }

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field must be a string.";

            return '';
        }

        $value = trim($value);

        if (
            mb_strlen($value) >
            $maxLength
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
            $config[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $errors[$key][] =
                "The {$label} field must be a string or null.";

            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen($value) >
            $maxLength
        ) {
            $errors[$key][] =
                "The {$label} may not be greater than {$maxLength} characters.";
        }

        return $value;
    }

    private function isSafeBackgroundImageReference(
        string $value,
    ): bool {
        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $value,
            ) === 1
        ) {
            return false;
        }

        /*
        * Page Builder uploads can use relative
        * public URLs such as:
        *
        * /storage/page-builder/...
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

        $scheme = parse_url(
            $value,
            PHP_URL_SCHEME,
        );

        if (! is_string($scheme)) {
            return false;
        }

        return in_array(
            strtolower($scheme),
            [
                'http',
                'https',
            ],
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    private function contactNumber(
        array $config,
        string $key,
        string $label,
        array &$errors,
    ): ?string {
        $value = $this->nullableString(
            config: $config,
            key: $key,
            maxLength: self::MAX_CONTACT_LENGTH,
            label: $label,
            errors: $errors,
        );

        if ($value === null) {
            return null;
        }

        if (
            preg_match(
                '/^[0-9+()\\-\\s.]+$/',
                $value,
            ) !== 1
        ) {
            $errors[$key][] =
                "The {$label} contains unsupported characters.";

            return $value;
        }

        $digits = preg_replace(
            '/\\D+/',
            '',
            $value,
        );

        if (
            ! is_string($digits) ||
            strlen($digits) < 6
        ) {
            $errors[$key][] =
                "The {$label} must contain at least 6 digits.";
        }

        return $value;
    }
}
