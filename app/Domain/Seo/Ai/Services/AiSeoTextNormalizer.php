<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

final class AiSeoTextNormalizer
{
    public const DEFAULT_TEXT_LIMIT =
        6000;

    public function normalize(
        ?string $value,
        int $limit = self::DEFAULT_TEXT_LIMIT,
    ): ?string {
        if (
            $value === null
            || $limit <= 0
        ) {
            return null;
        }

        $value =
            preg_replace(
                '/<(script|style)\b[^>]*>.*?<\/\1>/is',
                ' ',
                $value,
            )
            ?? $value;

        $value =
            html_entity_decode(
                strip_tags(
                    $value,
                ),
                ENT_QUOTES
                | ENT_HTML5,
                'UTF-8',
            );

        $value =
            str_replace(
                "\u{00A0}",
                ' ',
                $value,
            );

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            )
            ?? $value;

        $value =
            trim(
                $value,
            );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) <= $limit
        ) {
            return $value;
        }

        return rtrim(
            mb_substr(
                $value,
                0,
                $limit - 1,
            ),
        ).'…';
    }

    /**
     * Convert Page Builder config into bounded text-only
     * context.
     *
     * @param  array<string, mixed>  $config
     */
    public function pageBuilderConfig(
        array $config,
        int $limit = self::DEFAULT_TEXT_LIMIT,
    ): ?string {
        $values = [];

        $this->collectConfigStrings(
            $config,
            $values,
        );

        if ($values === []) {
            return null;
        }

        return $this->normalize(
            implode(
                ' | ',
                array_values(
                    array_unique(
                        $values,
                    ),
                ),
            ),
            $limit,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  list<string>  $values
     */
    private function collectConfigStrings(
        array $data,
        array &$values,
    ): void {
        foreach (
            $data as $key => $value
        ) {
            if (
                is_string(
                    $key,
                )
                && $this->skipKey(
                    $key,
                )
            ) {
                continue;
            }

            if (is_array($value)) {
                $this->collectConfigStrings(
                    $value,
                    $values,
                );

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $value =
                trim(
                    $value,
                );

            if (
                $value === ''
                || $this->looksLikeUrlOrTechnicalValue(
                    $value,
                )
            ) {
                continue;
            }

            $normalized =
                $this->normalize(
                    $value,
                    500,
                );

            if ($normalized !== null) {
                $values[] =
                    $normalized;
            }
        }
    }

    private function skipKey(
        string $key,
    ): bool {
        return preg_match(
            '/(?:^|_)(?:url|href|src|image|image_path|path|color|icon|id|uuid|file|mime|template)(?:$|_)/i',
            $key,
        ) === 1;
    }

    private function looksLikeUrlOrTechnicalValue(
        string $value,
    ): bool {
        if (
            preg_match(
                '/^(?:https?:\/\/|\/|#)/i',
                $value,
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^#[0-9a-f]{3,8}$/i',
                $value,
            ) === 1
        ) {
            return true;
        }

        return false;
    }
}
