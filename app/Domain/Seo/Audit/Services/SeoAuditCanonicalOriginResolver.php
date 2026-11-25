<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use DomainException;

final class SeoAuditCanonicalOriginResolver
{
    public function resolve(): string
    {
        $configured = trim((string) config('app.url'));

        if ($configured === '') {
            throw new DomainException(
                'SEO audit requires a configured application URL.',
            );
        }

        $parts = parse_url($configured);

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new DomainException(
                'SEO audit application URL must be an HTTP(S) origin.',
            );
        }

        $path = trim((string) ($parts['path'] ?? ''));

        if ($path !== '' && $path !== '/') {
            throw new DomainException(
                'SEO audit application URL must not contain a path prefix.',
            );
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port'])
            ? ':'.(string) $parts['port']
            : '';

        return $scheme.'://'.$host.$port;
    }
}
