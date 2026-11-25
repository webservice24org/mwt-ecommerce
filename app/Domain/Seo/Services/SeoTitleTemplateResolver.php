<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

final readonly class SeoTitleTemplateResolver
{
    public function resolve(
        string $template,
        string $pageTitle,
        string $separator,
        ?string $siteName,
    ): string {
        $resolved =
            strtr(
                $template,
                [
                    '{page_title}' => $pageTitle,

                    '{separator}' => $separator,

                    '{site_name}' => $siteName ?? '',
                ],
            );

        return $this->normalize(
            $resolved,
        );
    }

    private function normalize(
        string $value,
    ): string {
        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $value,
                ),
            );

        if (
            ! is_string(
                $value,
            )
        ) {
            return '';
        }

        $value =
            preg_replace(
                '/(?:\s*[|–—\-:]\s*)+$/u',
                '',
                $value,
            );

        return is_string(
            $value,
        )
            ? trim(
                $value,
            )
            : '';
    }
}
