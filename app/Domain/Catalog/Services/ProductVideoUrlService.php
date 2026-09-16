<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Enums\ProductVideoType;
use Illuminate\Validation\ValidationException;

final class ProductVideoUrlService
{
    public function validate(
        ProductVideoType $type,
        ?string $url,
    ): ?string {
        if ($type === ProductVideoType::Upload) {
            return null;
        }

        $url = trim(
            (string) $url,
        );

        if (
            $url === ''
            || filter_var(
                $url,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw ValidationException::withMessages([
                'url' => 'Enter a valid product video URL.',
            ]);
        }

        $scheme = parse_url(
            $url,
            PHP_URL_SCHEME,
        );

        if (
            ! is_string($scheme)
            || strtolower($scheme) !== 'https'
        ) {
            throw ValidationException::withMessages([
                'url' => 'Product video URLs must use HTTPS.',
            ]);
        }

        $host = parse_url(
            $url,
            PHP_URL_HOST,
        );

        if (! is_string($host)) {
            throw ValidationException::withMessages([
                'url' => 'Enter a valid product video URL.',
            ]);
        }

        $host = strtolower($host);

        if (str_starts_with(
            $host,
            'www.',
        )) {
            $host = substr(
                $host,
                4,
            );
        }

        /*
         * Upload has already returned above,
         * so only YouTube or Vimeo remain.
         */
        if (
            $type === ProductVideoType::Youtube
        ) {
            $allowedHosts = [
                'youtube.com',
                'm.youtube.com',
                'youtu.be',
            ];

            $errorMessage =
                'Enter a valid YouTube URL.';
        } else {
            $allowedHosts = [
                'vimeo.com',
                'player.vimeo.com',
            ];

            $errorMessage =
                'Enter a valid Vimeo URL.';
        }

        if (! in_array(
            $host,
            $allowedHosts,
            true,
        )) {
            throw ValidationException::withMessages([
                'url' => $errorMessage,
            ]);
        }

        return $url;
    }
}
