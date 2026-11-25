<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use DomainException;

final class SeoAuditUrlNormalizer
{
    public function normalizeInternal(
        string $reference,
        string $canonicalHost,
        ?string $baseUrl = null,
    ): ?string {
        $origin = $this->canonicalOrigin($canonicalHost);

        $absolute = $this->normalizeReference(
            reference: $reference,
            baseUrl: $baseUrl ?? $origin.'/',
        );

        if ($absolute === null) {
            return null;
        }

        $parts = parse_url($absolute);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        if (
            strtolower((string) $parts['host'])
                !== $this->host($origin)
            || $this->nonDefaultPort($absolute)
                !== $this->nonDefaultPort($origin)
        ) {
            return null;
        }

        return $absolute;
    }

    public function normalizeReference(
        string $reference,
        string $baseUrl,
    ): ?string {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (
            preg_match(
                '/^[a-z][a-z0-9+.-]*:/i',
                $reference,
            ) === 1
            && preg_match(
                '#^https?://#i',
                $reference,
            ) !== 1
        ) {
            return null;
        }

        $absolute = $this->absoluteReference(
            $reference,
            $baseUrl,
        );

        if ($absolute === null) {
            return null;
        }

        $parts = parse_url($absolute);

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
        ) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) $parts['host']);

        if ($host === '') {
            return null;
        }

        $port = isset($parts['port'])
            ? (int) $parts['port']
            : null;

        if (
            ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443)
        ) {
            $port = null;
        }

        $path = $this->normalizePath(
            isset($parts['path'])
                ? (string) $parts['path']
                : '/',
        );

        $query = isset($parts['query'])
            ? '?'.(string) $parts['query']
            : '';

        return $scheme
            .'://'
            .$host
            .($port === null ? '' : ':'.$port)
            .$path
            .$query;
    }

    public function hash(string $normalizedUrl): string
    {
        return hash('sha256', $normalizedUrl);
    }

    private function canonicalOrigin(string $canonicalHost): string
    {
        $canonicalHost = trim($canonicalHost);

        $parts = parse_url($canonicalHost);

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
        ) {
            throw new DomainException(
                'SEO audit canonical host must be an absolute HTTP(S) URL.',
            );
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new DomainException(
                'SEO audit canonical host must use HTTP or HTTPS.',
            );
        }

        $host = strtolower((string) $parts['host']);

        $port = isset($parts['port'])
            ? (int) $parts['port']
            : null;

        if (
            ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443)
        ) {
            $port = null;
        }

        return $scheme
            .'://'
            .$host
            .($port === null ? '' : ':'.$port);
    }

    private function host(string $absoluteUrl): string
    {
        $host = parse_url(
            $absoluteUrl,
            PHP_URL_HOST,
        );

        return strtolower(
            is_string($host)
                ? $host
                : '',
        );
    }

    private function nonDefaultPort(
        string $absoluteUrl,
    ): ?int {
        $parts = parse_url($absoluteUrl);

        if (! is_array($parts)) {
            return null;
        }

        $scheme =
            isset($parts['scheme'])
                ? strtolower(
                    (string) $parts['scheme'],
                )
                : null;

        $port =
            isset($parts['port'])
                ? (int) $parts['port']
                : null;

        if (
            ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443)
        ) {
            return null;
        }

        return $port;
    }

    private function absoluteReference(
        string $reference,
        string $baseUrl,
    ): ?string {
        if (preg_match('#^https?://#i', $reference) === 1) {
            return $reference;
        }

        $base = parse_url($baseUrl);

        if (
            ! is_array($base)
            || ! isset($base['scheme'], $base['host'])
        ) {
            return null;
        }

        $origin = strtolower((string) $base['scheme'])
            .'://'
            .strtolower((string) $base['host'])
            .(
                isset($base['port'])
                    ? ':'.(int) $base['port']
                    : ''
            );

        if (str_starts_with($reference, '//')) {
            return strtolower((string) $base['scheme'])
                .':'
                .$reference;
        }

        if (str_starts_with($reference, '#')) {
            $baseWithoutFragment = preg_replace(
                '/#.*$/',
                '',
                $baseUrl,
            );

            return is_string($baseWithoutFragment)
                ? $baseWithoutFragment
                : null;
        }

        if (str_starts_with($reference, '?')) {
            $basePath = isset($base['path'])
                ? (string) $base['path']
                : '/';

            return $origin
                .$this->normalizePath($basePath)
                .$reference;
        }

        [$referencePath, $suffix] =
            $this->splitPathAndSuffix($reference);

        if (str_starts_with($referencePath, '/')) {
            return $origin
                .$referencePath
                .$suffix;
        }

        $basePath = isset($base['path'])
            ? (string) $base['path']
            : '/';

        $directory = str_ends_with($basePath, '/')
            ? $basePath
            : dirname($basePath).'/';

        if ($directory === './') {
            $directory = '/';
        }

        return $origin
            .$directory
            .$referencePath
            .$suffix;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitPathAndSuffix(
        string $reference,
    ): array {
        $fragmentPosition = strpos(
            $reference,
            '#',
        );

        if ($fragmentPosition !== false) {
            $reference = substr(
                $reference,
                0,
                $fragmentPosition,
            );
        }

        $queryPosition = strpos(
            $reference,
            '?',
        );

        if ($queryPosition === false) {
            return [$reference, ''];
        }

        return [
            substr($reference, 0, $queryPosition),
            substr($reference, $queryPosition),
        ];
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $hadTrailingSlash =
            $path !== '/'
            && str_ends_with($path, '/');

        $segments = explode('/', $path);
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($normalized);

                continue;
            }

            $normalized[] = $segment;
        }

        $result = '/'.implode('/', $normalized);

        if ($hadTrailingSlash && $result !== '/') {
            $result .= '/';
        }

        return $result;
    }
}
