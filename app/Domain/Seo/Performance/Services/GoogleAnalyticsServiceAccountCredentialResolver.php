<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\GoogleAnalyticsServiceAccountCredentialsData;
use App\Domain\Seo\Performance\Exceptions\GoogleAnalyticsIntegrationException;
use JsonException;

final class GoogleAnalyticsServiceAccountCredentialResolver
{
    private const MAX_CREDENTIAL_FILE_BYTES = 131_072;

    public function resolve(): GoogleAnalyticsServiceAccountCredentialsData
    {
        $configured = config('seo_performance_google_analytics.service_account_json');

        if (! is_string($configured) || trim($configured) === '') {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_unavailable',
                'Google Analytics service-account credentials are not configured.',
            );
        }

        $path = $this->absolutePath(trim($configured));
        $realPath = realpath($path);

        if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_unavailable',
                'Google Analytics service-account credentials are unavailable.',
            );
        }

        $this->rejectPublicPath($realPath);

        $size = filesize($realPath);
        if ($size === false || $size < 1 || $size > self::MAX_CREDENTIAL_FILE_BYTES) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_invalid',
                'Google Analytics service-account credentials are invalid.',
            );
        }

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_unavailable',
                'Google Analytics service-account credentials could not be read.',
            );
        }

        try {
            $decoded = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_invalid',
                'Google Analytics service-account credentials are invalid.',
            );
        }

        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'service_account') {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_invalid',
                'Google Analytics credentials must be a Google service-account JSON file.',
            );
        }

        $clientEmail = $decoded['client_email'] ?? null;
        $privateKey = $decoded['private_key'] ?? null;
        $privateKeyId = $decoded['private_key_id'] ?? null;

        if (
            ! is_string($clientEmail)
            || trim($clientEmail) === ''
            || ! is_string($privateKey)
            || ! str_contains($privateKey, 'BEGIN PRIVATE KEY')
            || ($privateKeyId !== null && ! is_string($privateKeyId))
        ) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_invalid',
                'Google Analytics service-account credentials are missing required fields.',
            );
        }

        return new GoogleAnalyticsServiceAccountCredentialsData(
            clientEmail: trim($clientEmail),
            privateKey: $privateKey,
            privateKeyId: $privateKeyId === null ? null : trim($privateKeyId),
        );
    }

    private function absolutePath(string $path): string
    {
        if (
            str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        ) {
            return $path;
        }

        return base_path($path);
    }

    private function rejectPublicPath(string $credentialPath): void
    {
        $publicPath = realpath(public_path());
        if ($publicPath === false) {
            return;
        }

        $credential = $this->normalizedPath($credentialPath);
        $public = rtrim($this->normalizedPath($publicPath), '/').'/';

        if (str_starts_with($credential.'/', $public)) {
            throw new GoogleAnalyticsIntegrationException(
                'credentials_insecure',
                'Google Analytics service-account credentials must not be stored below public/.',
            );
        }
    }

    private function normalizedPath(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }
}
