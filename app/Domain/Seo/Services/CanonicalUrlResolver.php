<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

final readonly class CanonicalUrlResolver
{
    /**
     * Resolve the final absolute canonical URL.
     *
     * Precedence:
     *
     * 1. Explicit entity canonical override
     * 2. Canonical host + storefront path
     *
     * Query-string indexation policy is intentionally
     * handled later in 10.4D.3.
     */
    public function resolve(
        ?string $canonicalOverride,
        string $path,
        ?string $canonicalHost,
    ): string {
        $host =
            $this->normalizeHost(
                $canonicalHost,
            );

        $override =
            $this->normalizeOptionalUrl(
                $canonicalOverride,
            );

        if ($override !== null) {
            if (
                preg_match(
                    '/^https?:\/\//i',
                    $override,
                )
            ) {
                return $override;
            }

            if ($host !== null) {
                return $override === '/'
                    ? $host
                    : $host.$override;
            }

            return $override;
        }

        $normalizedPath =
            $this->normalizePath(
                $path,
            );

        if ($host === null) {
            return $normalizedPath;
        }

        return $normalizedPath === '/'
            ? $host
            : $host.$normalizedPath;
    }

    private function normalizeHost(
        ?string $host,
    ): ?string {
        if ($host === null) {
            return null;
        }

        $host =
            trim(
                $host,
            );

        if ($host === '') {
            return null;
        }

        if (
            ! preg_match(
                '/^https?:\/\//i',
                $host,
            )
        ) {
            $host =
                'https://'
                .$host;
        }

        $parts =
            parse_url(
                $host,
            );

        if (
            ! is_array(
                $parts,
            )
            || ! isset(
                $parts['scheme'],
                $parts['host'],
            )
        ) {
            return null;
        }

        $scheme =
            strtolower(
                (string) $parts['scheme'],
            );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            return null;
        }

        $normalized =
            $scheme
            .'://'
            .strtolower(
                (string) $parts['host'],
            );

        if (
            isset(
                $parts['port'],
            )
        ) {
            $normalized .=
                ':'
                .$parts['port'];
        }

        $basePath =
            isset(
                $parts['path'],
            )
            ? $this->normalizePath(
                (string) $parts['path'],
            )
            : '/';

        if (
            $basePath !== '/'
        ) {
            $normalized .=
                $basePath;
        }

        return rtrim(
            $normalized,
            '/',
        );
    }

    private function normalizeOptionalUrl(
        ?string $url,
    ): ?string {
        if ($url === null) {
            return null;
        }

        $url =
            trim(
                $url,
            );

        if ($url === '') {
            return null;
        }

        /*
         * Reject non-HTTP schemes such as:
         *
         * javascript:
         * data:
         * ftp:
         * file:
         * mailto:
         *
         * Canonical URLs must only use HTTP(S), or be a
         * relative storefront path.
         */
        if (
            preg_match(
                '/^[a-z][a-z0-9+.-]*:/i',
                $url,
            )
            && ! preg_match(
                '/^https?:\/\//i',
                $url,
            )
        ) {
            return null;
        }

        /*
         * Absolute HTTP(S) canonical override.
         */
        if (
            preg_match(
                '/^https?:\/\//i',
                $url,
            )
        ) {
            return $this
                ->normalizeAbsoluteUrl(
                    $url,
                );
        }

        /*
         * Relative canonical override.
         */
        return $this->normalizePath(
            $url,
        );
    }

    private function normalizeAbsoluteUrl(
        string $url,
    ): string {
        $parts =
            parse_url(
                $url,
            );

        if (
            ! is_array(
                $parts,
            )
            || ! isset(
                $parts['scheme'],
                $parts['host'],
            )
        ) {
            return $url;
        }

        $scheme =
            strtolower(
                (string) $parts['scheme'],
            );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            return $url;
        }

        $normalized =
            $scheme
            .'://'
            .strtolower(
                (string) $parts['host'],
            );

        if (
            isset(
                $parts['port'],
            )
        ) {
            $normalized .=
                ':'
                .$parts['port'];
        }

        $path =
            isset(
                $parts['path'],
            )
            ? $this->normalizePath(
                (string) $parts['path'],
            )
            : '/';

        if (
            $path !== '/'
        ) {
            $normalized .=
                $path;
        }

        /*
         * Explicit canonical overrides may intentionally
         * contain a query string.
         *
         * 10.4D.3 will decide when query strings are
         * indexable or should canonicalize elsewhere.
         */
        if (
            isset(
                $parts['query'],
            )
            && trim(
                (string) $parts['query'],
            ) !== ''
        ) {
            $normalized .=
                '?'
                .$parts['query'];
        }

        return $normalized;
    }

    private function normalizePath(
        string $path,
    ): string {
        $path =
            trim(
                $path,
            );

        if (
            $path === ''
            || $path === '/'
        ) {
            return '/';
        }

        /*
         * Drop fragments. A URL fragment is never part of
         * a canonical resource identity.
         */
        $path =
            explode(
                '#',
                $path,
                2,
            )[0];

        /*
         * Collapse repeated path separators without
         * touching a URL scheme because this method only
         * receives path values.
         */
        $path =
            preg_replace(
                '#/+#',
                '/',
                $path,
            ) ?? $path;

        $path =
            '/'
            .ltrim(
                $path,
                '/',
            );

        /*
         * Root keeps "/", normal routes do not keep a
         * trailing slash.
         */
        return rtrim(
            $path,
            '/',
        );
    }
}
