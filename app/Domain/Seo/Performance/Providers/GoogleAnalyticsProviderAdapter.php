<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Providers;

use App\Domain\Seo\Performance\Contracts\SeoPerformanceProviderAdapter;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Data\SeoPerformanceVerificationData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Exceptions\GoogleAnalyticsIntegrationException;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsDataApiClient;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsServiceAccountAccessTokenProvider;
use App\Models\SeoPerformanceConnection;

final readonly class GoogleAnalyticsProviderAdapter implements SeoPerformanceProviderAdapter
{
    public const MAX_PAGE_SIZE = 1000;

    public const MAX_ROWS_PER_SYNC = 25_000;

    public function __construct(
        private GoogleAnalyticsServiceAccountAccessTokenProvider $tokens,
        private GoogleAnalyticsDataApiClient $api,
    ) {}

    public function provider(): SeoPerformanceProvider
    {
        return SeoPerformanceProvider::GoogleAnalytics;
    }

    /** @return list<SeoPerformanceCapability> */
    public function capabilities(): array
    {
        return [SeoPerformanceCapability::AnalyticsLandingPages];
    }

    public function verify(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceVerificationData {
        if ($connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: 'provider_mismatch',
                message: 'This connection is not a Google Analytics connection.',
            );
        }

        try {
            $this->api->verifyProperty(
                $this->tokens->accessToken(),
                $connection->property_identifier,
            );
        } catch (GoogleAnalyticsIntegrationException $exception) {
            return new SeoPerformanceVerificationData(
                verified: false,
                capabilities: [],
                errorCode: $exception->failureCode,
                message: $exception->getMessage(),
            );
        }

        return new SeoPerformanceVerificationData(
            verified: true,
            capabilities: $this->capabilities(),
            message: 'Google Analytics Data API property access is verified.',
        );
    }

    public function fetchPage(
        SeoPerformanceConnection $connection,
        SeoPerformanceFetchRequestData $request,
    ): SeoPerformanceFetchPageData {
        if ($connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
            throw new GoogleAnalyticsIntegrationException(
                'provider_mismatch',
                'This connection is not a Google Analytics connection.',
            );
        }

        if ($connection->status !== SeoPerformanceConnectionStatus::Active) {
            throw new GoogleAnalyticsIntegrationException(
                'connection_inactive',
                'Only verified active Google Analytics connections may import landing-page data.',
            );
        }

        if ($request->scope !== SeoPerformanceSyncScope::LandingAnalytics) {
            throw new GoogleAnalyticsIntegrationException(
                'scope_unsupported',
                'Google Analytics does not support this performance synchronization scope.',
            );
        }

        if ($request->rowLimit < 1 || $request->rowLimit > self::MAX_PAGE_SIZE) {
            throw new GoogleAnalyticsIntegrationException(
                'request_invalid',
                'Google Analytics import pages are limited to 1,000 rows.',
            );
        }

        $offset = $this->decodePageToken($request->pageToken);
        $apiRowLimit = min(
            $request->rowLimit,
            self::MAX_ROWS_PER_SYNC - $offset,
        );

        if ($apiRowLimit < 1) {
            throw new GoogleAnalyticsIntegrationException(
                'page_token_invalid',
                'The Google Analytics import continuation token is outside the supported bounds.',
            );
        }

        $page = $this->api->landingPages(
            $this->tokens->accessToken(),
            $connection->property_identifier,
            $request->dateFrom->format('Y-m-d'),
            $request->dateTo->format('Y-m-d'),
            $apiRowLimit,
            $offset,
        );

        $received = count($page->rows);
        $nextOffset = $offset + $apiRowLimit;
        $capReached = $nextOffset >= self::MAX_ROWS_PER_SYNC
            && $page->rowCount > self::MAX_ROWS_PER_SYNC;
        $complete = $received < $apiRowLimit
            || $nextOffset >= $page->rowCount
            || $capReached;

        return new SeoPerformanceFetchPageData(
            rows: $page->rows,
            nextPageToken: $complete ? null : 'offset:'.$nextOffset,
            complete: $complete,
            truncated: $capReached,
        );
    }

    private function decodePageToken(?string $token): int
    {
        if ($token === null || $token === '') {
            return 0;
        }

        if (preg_match('/^offset:(\d+)$/', $token, $matches) !== 1) {
            throw new GoogleAnalyticsIntegrationException(
                'page_token_invalid',
                'The Google Analytics import continuation token is invalid.',
            );
        }

        $offset = (int) $matches[1];
        if ($offset < 0 || $offset >= self::MAX_ROWS_PER_SYNC) {
            throw new GoogleAnalyticsIntegrationException(
                'page_token_invalid',
                'The Google Analytics import continuation token is outside the supported bounds.',
            );
        }

        return $offset;
    }
}
