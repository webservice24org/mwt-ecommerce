<?php

declare(strict_types=1);

namespace App\Domain\WebsiteSettings;

final class BuilderStyleSchema
{
    /**
     * @param  list<string>  $sections
     * @param  array<string, list<string>>  $errors
     * @return array<string, array<string, string|int>>
     */
    public function normalize(mixed $value, array $sections, array &$errors): array
    {
        if (! is_array($value)) {
            $errors['style'][] = 'The style configuration must be an object.';

            return [];
        }

        $result = [];

        foreach ($value as $section => $fields) {
            $path = 'style.'.$section;

            if (! is_string($section) || ! in_array($section, $sections, true)) {
                $errors[$path][] = 'Unknown style section.';

                continue;
            }

            if (! is_array($fields)) {
                $errors[$path][] = 'The style section must be an object.';

                continue;
            }

            $normalized = [];

            foreach ($fields as $key => $field) {
                $fieldPath = $path.'.'.$key;

                if ($key === 'text_color' || $key === 'background_color') {
                    if (! is_string($field) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $field) !== 1) {
                        $errors[$fieldPath][] = 'Use a six-digit hexadecimal color, such as #112233.';

                        continue;
                    }

                    $normalized[$key] = strtolower($field);
                } elseif ($key === 'text_size') {
                    if (! is_int($field) || $field < 10 || $field > 24) {
                        $errors[$fieldPath][] = 'Text size must be an integer between 10 and 24 pixels.';

                        continue;
                    }

                    $normalized[$key] = $field;
                } else {
                    $errors[$fieldPath][] = 'Unknown style property.';
                }
            }

            if ($normalized !== []) {
                $result[$section] = $normalized;
            }
        }

        return $result;
    }
}
