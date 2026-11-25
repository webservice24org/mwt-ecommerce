<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\GoogleAnalyticsLandingReportPageData;
use App\Domain\Seo\Performance\Exceptions\GoogleAnalyticsIntegrationException;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GoogleAnalyticsDataApiClient
{
    public const RUN_REPORT_BASE_URI = 'https://analyticsdata.googleapis.com/v1beta/properties';

    public function verifyProperty(
        string $accessToken,
        string $propertyIdentifier,
    ): void {
        $response = $this->postRunReport(
            $accessToken,
            $propertyIdentifier,
            [
                'dateRanges' => [[
                    'startDate' => '7daysAgo',
                    'endDate' => 'yesterday',
                ]],
                'dimensions' => [['name' => 'date']],
                'metrics' => [['name' => 'sessions']],
                'limit' => 1,
                'offset' => 0,
            ],
        );

        $this->assertSuccessful($response, true);
    }

    public function landingPages(
        string $accessToken,
        string $propertyIdentifier,
        string $dateFrom,
        string $dateTo,
        int $limit,
        int $offset,
    ): GoogleAnalyticsLandingReportPageData {
        if ($limit < 1 || $limit > 1000 || $offset < 0) {
            throw new GoogleAnalyticsIntegrationException(
                'request_invalid',
                'Google Analytics landing-page pagination is outside the supported bounds.',
            );
        }

        $response = $this->postRunReport(
            $accessToken,
            $propertyIdentifier,
            [
                'dateRanges' => [[
                    'startDate' => $dateFrom,
                    'endDate' => $dateTo,
                ]],
                'dimensions' => [
                    ['name' => 'date'],
                    ['name' => 'landingPage'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'engagedSessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'newUsers'],
                    ['name' => 'keyEvents'],
                    ['name' => 'totalRevenue'],
                ],
                'dimensionFilter' => [
                    'notExpression' => [
                        'filter' => [
                            'fieldName' => 'landingPage',
                            'stringFilter' => [
                                'matchType' => 'EXACT',
                                'value' => '(not set)',
                            ],
                        ],
                    ],
                ],
                'orderBys' => [
                    ['dimension' => ['dimensionName' => 'date']],
                    ['dimension' => ['dimensionName' => 'landingPage']],
                ],
                'limit' => $limit,
                'offset' => $offset,
            ],
        );

        $this->assertSuccessful($response, false);
        $data = $response->json();

        if (! is_array($data)) {
            throw $this->invalidResponse();
        }

        $this->assertHeaders($data);

        $rowCount = $data['rowCount'] ?? 0;
        if (! is_int($rowCount) && ! (is_string($rowCount) && ctype_digit($rowCount))) {
            throw $this->invalidResponse();
        }
        $rowCount = (int) $rowCount;

        $rawRows = $data['rows'] ?? [];
        if (! is_array($rawRows) || ! array_is_list($rawRows)) {
            throw $this->invalidResponse();
        }

        $rows = [];
        foreach ($rawRows as $rawRow) {
            if (! is_array($rawRow)) {
                throw $this->invalidResponse();
            }

            $dimensions = $rawRow['dimensionValues'] ?? null;
            $metrics = $rawRow['metricValues'] ?? null;
            if (
                ! is_array($dimensions)
                || ! array_is_list($dimensions)
                || count($dimensions) !== 2
                || ! is_array($metrics)
                || ! array_is_list($metrics)
                || count($metrics) !== 6
            ) {
                throw $this->invalidResponse();
            }

            $dateRaw = $this->cellValue($dimensions[0] ?? null);
            $landingPage = trim($this->cellValue($dimensions[1] ?? null));
            $date = DateTimeImmutable::createFromFormat('!Ymd', $dateRaw);

            if (
                $date === false
                || $date->format('Ymd') !== $dateRaw
                || $landingPage === ''
                || $landingPage === '(not set)'
            ) {
                throw $this->invalidResponse();
            }

            $sessions = $this->numericCell($metrics[0] ?? null);
            $engagedSessions = $this->numericCell($metrics[1] ?? null);
            $activeUsers = $this->numericCell($metrics[2] ?? null);
            $newUsers = $this->numericCell($metrics[3] ?? null);
            $keyEvents = $this->numericCell($metrics[4] ?? null);
            $totalRevenue = $this->numericCell($metrics[5] ?? null);

            if (
                $sessions < 0
                || $engagedSessions < 0
                || $activeUsers < 0
                || $newUsers < 0
                || $keyEvents < 0
            ) {
                throw $this->invalidResponse();
            }

            $rows[] = [
                'date' => $date->format('Y-m-d'),
                'landing_page' => $landingPage,
                'sessions' => $sessions,
                'engaged_sessions' => $engagedSessions,
                'active_users' => $activeUsers,
                'new_users' => $newUsers,
                'key_events' => $keyEvents,
                'total_revenue' => $totalRevenue,
            ];
        }

        return new GoogleAnalyticsLandingReportPageData(
            rows: $rows,
            rowCount: $rowCount,
        );
    }

    /** @param array<string, mixed> $payload */
    private function postRunReport(
        string $accessToken,
        string $propertyIdentifier,
        array $payload,
    ): Response {
        $accessToken = trim($accessToken);
        $propertyIdentifier = trim($propertyIdentifier);

        if (
            $accessToken === ''
            || preg_match('/^[1-9][0-9]{0,19}$/', $propertyIdentifier) !== 1
        ) {
            throw new GoogleAnalyticsIntegrationException(
                'property_invalid',
                'A valid numeric Google Analytics property ID is required.',
            );
        }

        try {
            return Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['User-Agent' => 'MWT-SEO-Performance/1.0'])
                ->connectTimeout(5)
                ->timeout(15)
                ->post(
                    self::RUN_REPORT_BASE_URI.'/'.$propertyIdentifier.':runReport',
                    $payload,
                );
        } catch (ConnectionException) {
            throw new GoogleAnalyticsIntegrationException(
                'provider_unavailable',
                'Google Analytics is temporarily unavailable.',
            );
        }
    }

    private function assertSuccessful(Response $response, bool $verification): void
    {
        if ($response->status() === 401 || $response->status() === 403) {
            throw new GoogleAnalyticsIntegrationException(
                'authorization_denied',
                'Google Analytics denied access to the configured property.',
            );
        }

        if ($response->status() === 404) {
            throw new GoogleAnalyticsIntegrationException(
                'property_not_accessible',
                'The configured Google Analytics property was not found for this credential.',
            );
        }

        if (! $response->successful()) {
            throw new GoogleAnalyticsIntegrationException(
                'provider_unavailable',
                $verification
                    ? 'Google Analytics could not verify the configured property.'
                    : 'Google Analytics could not return the requested landing-page report.',
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function assertHeaders(array $data): void
    {
        $dimensions = $data['dimensionHeaders'] ?? null;
        $metrics = $data['metricHeaders'] ?? null;

        if (! is_array($dimensions) || ! is_array($metrics)) {
            throw $this->invalidResponse();
        }

        $dimensionNames = array_map(
            static fn (mixed $value): mixed => is_array($value) ? ($value['name'] ?? null) : null,
            $dimensions,
        );
        $metricNames = array_map(
            static fn (mixed $value): mixed => is_array($value) ? ($value['name'] ?? null) : null,
            $metrics,
        );

        if ($dimensionNames !== ['date', 'landingPage']) {
            throw $this->invalidResponse();
        }

        if ($metricNames !== [
            'sessions',
            'engagedSessions',
            'activeUsers',
            'newUsers',
            'keyEvents',
            'totalRevenue',
        ]) {
            throw $this->invalidResponse();
        }
    }

    private function cellValue(mixed $cell): string
    {
        $value = is_array($cell) ? ($cell['value'] ?? null) : null;
        if (! is_string($value)) {
            throw $this->invalidResponse();
        }

        return $value;
    }

    private function numericCell(mixed $cell): float
    {
        $value = $this->cellValue($cell);
        if (! is_numeric($value)) {
            throw $this->invalidResponse();
        }

        $numeric = (float) $value;
        if (! is_finite($numeric)) {
            throw $this->invalidResponse();
        }

        return $numeric;
    }

    private function invalidResponse(): GoogleAnalyticsIntegrationException
    {
        return new GoogleAnalyticsIntegrationException(
            'provider_response_invalid',
            'Google Analytics returned an invalid Data API response.',
        );
    }
}
