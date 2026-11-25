<?php

declare(strict_types=1);

namespace App\Domain\WebsiteSettings;

final class ProductCardStyleSchema
{
    public const COLORS = [
        'background_color',
        'title_color',
        'title_hover_color',
        'text_color',
        'regular_price_color',
        'offer_price_color',
        'add_background_color',
        'add_text_color',
        'add_border_color',
        'add_hover_background_color',
        'add_hover_text_color',
        'order_background_color',
        'order_text_color',
        'order_border_color',
        'order_hover_background_color',
        'order_hover_text_color',
    ];

    public const RADII = ['add_border_radius', 'order_border_radius'];

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, string|int>
     */
    public function normalize(mixed $value, array &$errors): array
    {
        if (! is_array($value)) {
            $errors['product_cards'][] = 'Product card styles must be an object.';

            return [];
        }

        $result = [];

        foreach ($value as $key => $field) {
            $path = 'product_cards.'.$key;

            if (is_string($key) && in_array($key, self::COLORS, true)) {
                if (! is_string($field) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $field) !== 1) {
                    $errors[$path][] = 'Use a six-digit hexadecimal color.';

                    continue;
                }

                $result[$key] = strtolower($field);
            } elseif ($key === 'title_hover_decoration') {
                if (! is_string($field) || ! in_array($field, ['underline', 'none'], true)) {
                    $errors[$path][] = 'Choose underline or none.';

                    continue;
                }

                $result[$key] = $field;
            } elseif (is_string($key) && in_array($key, self::RADII, true)) {
                if (! is_int($field) || $field < 0 || $field > 32) {
                    $errors[$path][] = 'Use an integer between 0 and 32 pixels.';

                    continue;
                }

                $result[$key] = $field;
            } else {
                $errors[$path][] = 'Unknown product card style property.';
            }
        }

        return $result;
    }
}
