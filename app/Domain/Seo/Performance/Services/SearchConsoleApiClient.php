<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\SearchConsolePropertyAccessData;
use App\Domain\Seo\Performance\Data\SearchConsoleSearchAnalyticsPageData;
use App\Domain\Seo\Performance\Exceptions\SearchConsoleIntegrationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class SearchConsoleApiClient
{
    public const SITES_URI = 'https://www.googleapis.com/webmasters/v3/sites';

    public function property(
        string $accessToken,
        string $propertyIdentifier,
    ): SearchConsolePropertyAccessData {
        $accessToken = trim($accessToken);
        $propertyIdentifier = trim($propertyIdentifier);

        if ($accessToken === '' || $propertyIdentifier === '') {
            throw new SearchConsoleIntegrationException(
                'property_invalid',
                'A valid Search Console property identifier is required.',
            );
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'MWT-SEO-Performance/1.0'])
                ->connectTimeout(5)
                ->timeout(10)
                ->get(self::SITES_URI.'/'.rawurlencode($propertyIdentifier));
        } catch (ConnectionException) {
            throw new SearchConsoleIntegrationException(
                'provider_unavailable',
                'Google Search Console is temporarily unavailable.',
            );
        }

        $this->assertSuccessful($response, 'verify the configured property');

        $data = $response->json();
        $siteUrl = is_array($data) ? ($data['siteUrl'] ?? null) : null;
        $permissionLevel = is_array($data) ? ($data['permissionLevel'] ?? null) : null;

        if (! is_string($siteUrl) || ! is_string($permissionLevel)) {
            throw new SearchConsoleIntegrationException(
                'provider_response_invalid',
                'Google Search Console returned an invalid property response.',
            );
        }

        return new SearchConsolePropertyAccessData(
            siteUrl: $siteUrl,
            permissionLevel: $permissionLevel,
        );
    }

    /**
     * @param  list<string>  $dimensions
     */
    public function searchAnalytics(
        string $accessToken,
        string $propertyIdentifier,
        string $startDate,
        string $endDate,
        array $dimensions,
        int $rowLimit,
        int $startRow,
    ): SearchConsoleSearchAnalyticsPageData {
        $accessToken = trim($accessToken);
        $propertyIdentifier = trim($propertyIdentifier);

        if ($accessToken === '' || $propertyIdentifier === '') {
            throw new SearchConsoleIntegrationException(
                'property_invalid',
                'A valid Search Console property identifier is required.',
            );
        }

        if ($rowLimit < 1 || $rowLimit > 1000 || $startRow < 0) {
            throw new SearchConsoleIntegrationException(
                'request_invalid',
                'Search Console import pagination is outside the supported bounds.',
            );
        }

        if ($dimensions === [] || count(array_unique($dimensions)) !== count($dimensions)) {
            throw new SearchConsoleIntegrationException(
                'request_invalid',
                'Search Console import dimensions are invalid.',
            );
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['User-Agent' => 'MWT-SEO-Performance/1.0'])
                ->connectTimeout(5)
                ->timeout(20)
                ->post(
                    self::SITES_URI.'/'.rawurlencode($propertyIdentifier).'/searchAnalytics/query',
                    [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'dimensions' => $dimensions,
                        'type' => 'web',
                        'aggregationType' => 'auto',
                        'rowLimit' => $rowLimit,
                        'startRow' => $startRow,
                        'dataState' => 'final',
                    ],
                );
        } catch (ConnectionException) {
            throw new SearchConsoleIntegrationException(
                'provider_unavailable',
                'Google Search Console is temporarily unavailable.',
            );
        }

        $this->assertSuccessful($response, 'retrieve Search Analytics data');

        $data = $response->json();
        $rows = is_array($data) ? ($data['rows'] ?? []) : null;

        if (! is_array($rows)) {
            throw new SearchConsoleIntegrationException(
                'provider_response_invalid',
                'Google Search Console returned an invalid Search Analytics response.',
            );
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new SearchConsoleIntegrationException(
                    'provider_response_invalid',
                    'Google Search Console returned an invalid Search Analytics row.',
                );
            }

            $keys = $row['keys'] ?? null;
            $clicks = $row['clicks'] ?? null;
            $impressions = $row['impressions'] ?? null;
            $ctr = $row['ctr'] ?? null;
            $position = $row['position'] ?? null;

            if (
                ! is_array($keys)
                || ! $this->isStringList($keys)
                || ! is_numeric($clicks)
                || ! is_numeric($impressions)
                || ! is_numeric($ctr)
                || ! is_numeric($position)
            ) {
                throw new SearchConsoleIntegrationException(
                    'provider_response_invalid',
                    'Google Search Console returned an invalid Search Analytics row.',
                );
            }

            $normalizedKeys = array_map(
                static fn (mixed $key): string => (string) $key,
                array_values($keys),
            );

            $normalized[] = [
                'keys' => $normalizedKeys,
                'clicks' => (float) $clicks,
                'impressions' => (float) $impressions,
                'ctr' => (float) $ctr,
                'position' => (float) $position,
            ];
        }

        return new SearchConsoleSearchAnalyticsPageData($normalized);
    }

    private function assertSuccessful(Response $response, string $operation): void
    {
        if ($response->status() === 401 || $response->status() === 403) {
            throw new SearchConsoleIntegrationException(
                'authorization_denied',
                'Google Search Console denied access to the configured property.',
            );
        }

        if ($response->status() === 404) {
            throw new SearchConsoleIntegrationException(
                'property_not_accessible',
                'The configured Search Console property was not found for this credential.',
            );
        }

        if ($response->status() === 400) {
            throw new SearchConsoleIntegrationException(
                'request_invalid',
                'Google Search Console rejected the performance request.',
            );
        }

        if ($response->status() === 429) {
            throw new SearchConsoleIntegrationException(
                'provider_rate_limited',
                'Google Search Console temporarily rate limited this request.',
            );
        }

        if (! $response->successful()) {
            throw new SearchConsoleIntegrationException(
                'provider_unavailable',
                'Google Search Console could not '.$operation.'.',
            );
        }
    }

    /** @param array<array-key, mixed> $values */
    private function isStringList(array $values): bool
    {
        if (! array_is_list($values)) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_string($value)) {
                return false;
            }
        }

        return true;
    }
}
