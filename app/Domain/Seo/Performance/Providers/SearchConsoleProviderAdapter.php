<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Providers;

use App\Domain\Seo\Performance\Contracts\SeoPerformanceProviderAdapter;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Data\SeoPerformanceVerificationData;
use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Exceptions\SearchConsoleIntegrationException;
use App\Domain\Seo\Performance\Services\SearchConsoleApiClient;
use App\Domain\Seo\Performance\Services\SearchConsoleServiceAccountAccessTokenProvider;
use App\Models\SeoPerformanceConnection;

final readonly class SearchConsoleProviderAdapter implements SeoPerformanceProviderAdapter
{
    public const MAX_PAGE_SIZE = 1000;

    public const MAX_ROWS_PER_DIMENSION = 25000;

    public function __construct(
        private SearchConsoleServiceAccountAccessTokenProvider $tokens,
        private SearchConsoleApiClient $api,
    ) {}

    public function provider(): SeoPerformanceProvider
    {
        return SeoPerformanceProvider::SearchConsole;
    }

    /** @return list<SeoPerformanceCapability> */
    public function capabilities(): array
    {
        return [
            SeoPerformanceCapability::SearchQueries,
            SeoPerformanceCapability::SearchLandingPages,
        ];
    }

    public function verify(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceVerificationData {
        if ($connection->provider !== SeoPerformanceProvider::SearchConsole) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: 'provider_mismatch',
                message: 'This connection is not a Google Search Console connection.',
            );
        }

        try {
            $access = $this->api->property(
                $this->tokens->accessToken(),
                $connection->property_identifier,
            );
        } catch (SearchConsoleIntegrationException $exception) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: $exception->failureCode,
                message: $exception->getMessage(),
            );
        }

        if (! hash_equals($connection->property_identifier, $access->siteUrl)) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: 'property_mismatch',
                message: 'Google returned a different Search Console property identifier.',
            );
        }

        if (! $access->isVerified()) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: 'property_unverified',
                message: 'The Search Console credential does not have verified access to this property.',
            );
        }

        return new SeoPerformanceVerificationData(
            verified: true,
            capabilities: $this->capabilities(),
            message: 'Google Search Console property access is verified.',
        );
    }

    public function fetchPage(
        SeoPerformanceConnection $connection,
        SeoPerformanceFetchRequestData $request,
    ): SeoPerformanceFetchPageData {
        if ($connection->provider !== SeoPerformanceProvider::SearchConsole) {
            throw new SearchConsoleIntegrationException(
                'provider_mismatch',
                'This connection is not a Google Search Console connection.',
            );
        }

        if ($connection->status !== SeoPerformanceConnectionStatus::Active) {
            throw new SearchConsoleIntegrationException(
                'connection_inactive',
                'Only verified active Search Console connections may import performance data.',
            );
        }

        if ($request->scope !== SeoPerformanceSyncScope::SearchPerformance) {
            throw new SearchConsoleIntegrationException(
                'scope_unsupported',
                'Google Search Console does not support this performance synchronization scope.',
            );
        }

        if ($request->rowLimit < 1 || $request->rowLimit > self::MAX_PAGE_SIZE) {
            throw new SearchConsoleIntegrationException(
                'request_invalid',
                'Search Console import pages are limited to 1,000 rows.',
            );
        }

        [$dimension, $startRow, $queryTruncated] = $this->decodePageToken(
            $request->pageToken,
        );

        $apiRowLimit = min(
            $request->rowLimit,
            self::MAX_ROWS_PER_DIMENSION - $startRow,
        );

        if ($apiRowLimit < 1) {
            throw new SearchConsoleIntegrationException(
                'page_token_invalid',
                'The Search Console import continuation token is outside the supported bounds.',
            );
        }

        $page = $this->api->searchAnalytics(
            $this->tokens->accessToken(),
            $connection->property_identifier,
            $request->dateFrom->format('Y-m-d'),
            $request->dateTo->format('Y-m-d'),
            ['date', $dimension === SearchConsolePerformanceDimension::Query ? 'query' : 'page'],
            $apiRowLimit,
            $startRow,
        );

        $rows = [];
        foreach ($page->rows as $row) {
            if (count($row['keys']) !== 2) {
                throw new SearchConsoleIntegrationException(
                    'provider_response_invalid',
                    'Google Search Console returned an unexpected Search Analytics dimension shape.',
                );
            }

            [$date, $value] = $row['keys'];
            if (! $this->isValidDate($date) || trim($value) === '') {
                throw new SearchConsoleIntegrationException(
                    'provider_response_invalid',
                    'Google Search Console returned an invalid Search Analytics dimension value.',
                );
            }

            if (
                $row['clicks'] < 0
                || $row['impressions'] < 0
                || $row['ctr'] < 0
                || $row['ctr'] > 1
                || $row['position'] < 0
            ) {
                throw new SearchConsoleIntegrationException(
                    'provider_response_invalid',
                    'Google Search Console returned invalid Search Analytics metrics.',
                );
            }

            $rows[] = [
                'dimension_type' => $dimension->value,
                'search_type' => 'web',
                'date' => $date,
                'dimension_value' => $value,
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr' => $row['ctr'],
                'position' => $row['position'],
            ];
        }

        $received = count($page->rows);
        $nextOffset = $startRow + $apiRowLimit;
        $dimensionCapReached = $received === $apiRowLimit
            && $nextOffset >= self::MAX_ROWS_PER_DIMENSION;
        $dimensionComplete = $received < $apiRowLimit || $dimensionCapReached;

        if ($dimension === SearchConsolePerformanceDimension::Query) {
            if (! $dimensionComplete) {
                return new SeoPerformanceFetchPageData(
                    rows: $rows,
                    nextPageToken: 'query:'.$nextOffset,
                    complete: false,
                );
            }

            $wasTruncated = $dimensionCapReached;

            return new SeoPerformanceFetchPageData(
                rows: $rows,
                nextPageToken: 'page:0:q'.($wasTruncated ? '1' : '0'),
                complete: false,
                truncated: $wasTruncated,
            );
        }

        $truncated = $queryTruncated || $dimensionCapReached;

        if (! $dimensionComplete) {
            return new SeoPerformanceFetchPageData(
                rows: $rows,
                nextPageToken: 'page:'.$nextOffset.':q'.($queryTruncated ? '1' : '0'),
                complete: false,
                truncated: $queryTruncated,
            );
        }

        return new SeoPerformanceFetchPageData(
            rows: $rows,
            nextPageToken: null,
            complete: true,
            truncated: $truncated,
        );
    }

    /** @return array{SearchConsolePerformanceDimension, int, bool} */
    private function decodePageToken(?string $token): array
    {
        if ($token === null || $token === '') {
            return [SearchConsolePerformanceDimension::Query, 0, false];
        }

        if (preg_match('/^query:(\d+)$/', $token, $matches) === 1) {
            $offset = (int) $matches[1];
            $this->assertOffset($offset);

            return [SearchConsolePerformanceDimension::Query, $offset, false];
        }

        if (preg_match('/^page:(\d+):q([01])$/', $token, $matches) === 1) {
            $offset = (int) $matches[1];
            $this->assertOffset($offset);

            return [
                SearchConsolePerformanceDimension::LandingPage,
                $offset,
                $matches[2] === '1',
            ];
        }

        throw new SearchConsoleIntegrationException(
            'page_token_invalid',
            'The Search Console import continuation token is invalid.',
        );
    }

    private function assertOffset(int $offset): void
    {
        if ($offset < 0 || $offset >= self::MAX_ROWS_PER_DIMENSION) {
            throw new SearchConsoleIntegrationException(
                'page_token_invalid',
                'The Search Console import continuation token is outside the supported bounds.',
            );
        }
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
