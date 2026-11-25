<?php

declare(strict_types=1);

namespace App\Domain\WebsiteSettings;

use App\Models\ThemeSetting;

final class AdminBrandingSchema
{
    public const SECTION = 'admin_branding';

    public const IMAGE_FIELDS = ['login_logo', 'sidebar_logo', 'background_image'];

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, string>
     */
    public function normalize(mixed $value, array &$errors): array
    {
        if (! is_array($value)) {
            $errors['admin_branding'][] = 'Admin branding must be an object.';

            return [];
        }

        $result = [];

        foreach ($value as $key => $field) {
            $path = 'admin_branding.'.$key;

            if ($key === 'background_mode' && is_string($field) && in_array($field, ['default', 'color', 'image'], true)) {
                $result[$key] = $field;
            } elseif ($key === 'background_color' && is_string($field) && preg_match('/\A#[0-9a-fA-F]{6}\z/', $field) === 1) {
                $result[$key] = strtolower($field);
            } elseif (is_string($key) && in_array($key, self::IMAGE_FIELDS, true) && is_string($field)
                && preg_match('~\A/storage/branding/admin/'.$key.'/[A-Za-z0-9]{40}\.(?:png|jpe?g|webp)\z~', $field) === 1) {
                $result[$key] = $field;
            } else {
                $errors[$path][] = 'Invalid branding value. Use the provided color picker or image uploader.';
            }
        }

        if (($result['background_mode'] ?? 'default') === 'color' && ! isset($result['background_color'])) {
            $errors['admin_branding.background_color'][] = 'Choose a background color.';
        }

        if (($result['background_mode'] ?? 'default') === 'image' && ! isset($result['background_image'])) {
            $errors['admin_branding.background_image'][] = 'Upload a background image.';
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public static function saved(): array
    {
        $setting = ThemeSetting::query()->find(self::SECTION);
        $errors = [];
        $config = (new self)->normalize($setting->config ?? [], $errors);

        if (($config['background_mode'] ?? 'default') === 'image' && ! isset($config['background_image'])) {
            unset($config['background_mode']);
        }

        if (($config['background_mode'] ?? 'default') === 'color' && ! isset($config['background_color'])) {
            unset($config['background_mode']);
        }

        return $config;
    }
}
