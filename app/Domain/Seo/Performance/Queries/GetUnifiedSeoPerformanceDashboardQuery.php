<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Queries;

use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class GetUnifiedSeoPerformanceDashboardQuery
{
    private const RUN_SELECTOR_LIMIT = 10;

    private const PAGE_CANDIDATES_PER_SOURCE = 200;

    private const PAGES_PER_PAGE = 20;

    private const INSIGHT_LIMIT = 12;

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $searchRunPublicId,
        ?string $analyticsRunPublicId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $pageSearch,
        int $pagesPage,
    ): array {
        $pagesPage = max(1, $pagesPage);

        $searchRuns = $this->eligibleRuns(
            provider: SeoPerformanceProvider::SearchConsole,
            scope: SeoPerformanceSyncScope::SearchPerformance,
        );
        $analyticsRuns = $this->eligibleRuns(
            provider: SeoPerformanceProvider::GoogleAnalytics,
            scope: SeoPerformanceSyncScope::LandingAnalytics,
        );

        $searchRun = $this->selectedRun($searchRuns, $searchRunPublicId);
        $analyticsRun = $this->selectedRun($analyticsRuns, $analyticsRunPublicId);

        $runChoices = [
            'search_console' => (clone $searchRuns)
                ->latest('seo_performance_sync_runs.id')
                ->limit(self::RUN_SELECTOR_LIMIT)
                ->get()
                ->map(fn (SeoPerformanceSyncRun $run): array => $this->runRow($run))
                ->values()
                ->all(),
            'google_analytics' => (clone $analyticsRuns)
                ->latest('seo_performance_sync_runs.id')
                ->limit(self::RUN_SELECTOR_LIMIT)
                ->get()
                ->map(fn (SeoPerformanceSyncRun $run): array => $this->runRow($run))
                ->values()
                ->all(),
        ];

        $range = $this->effectiveRange(
            searchRun: $searchRun,
            analyticsRun: $analyticsRun,
            requestedFrom: $dateFrom,
            requestedTo: $dateTo,
        );

        $searchSummary = $this->emptySearchMetrics();
        $analyticsSummary = $this->emptyAnalyticsMetrics();
        $daily = [];
        $pages = [];
        $insights = [];
        $pagination = $this->pagination(1, self::PAGES_PER_PAGE, 0, 1);

        if ($range['has_overlap'] && $searchRun !== null && $analyticsRun !== null) {
            $searchSummary = $this->searchSummary(
                runId: $searchRun->getKey(),
                dateFrom: $range['from'],
                dateTo: $range['to'],
            );
            $analyticsSummary = $this->analyticsSummary(
                runId: $analyticsRun->getKey(),
                dateFrom: $range['from'],
                dateTo: $range['to'],
            );
            $daily = $this->dailyComparison(
                searchRunId: $searchRun->getKey(),
                analyticsRunId: $analyticsRun->getKey(),
                dateFrom: $range['from'],
                dateTo: $range['to'],
            );

            $pageReport = $this->pageComparison(
                searchRunId: $searchRun->getKey(),
                analyticsRunId: $analyticsRun->getKey(),
                dateFrom: $range['from'],
                dateTo: $range['to'],
                search: $pageSearch,
                page: $pagesPage,
            );

            $pages = $pageReport['rows'];
            $pagination = $pageReport['pagination'];
            $insights = $pageReport['insights'];
        }

        return [
            'run_choices' => $runChoices,
            'selected_runs' => [
                'search_console' => $searchRun === null ? null : $this->runRow($searchRun),
                'google_analytics' => $analyticsRun === null ? null : $this->runRow($analyticsRun),
            ],
            'source_status' => [
                'search_console' => [
                    'available' => $searchRun !== null,
                    'truncated' => $searchRun === null ? false : $searchRun->provider_truncated,
                ],
                'google_analytics' => [
                    'available' => $analyticsRun !== null,
                    'truncated' => $analyticsRun === null ? false : $analyticsRun->provider_truncated,
                ],
            ],
            'date_range' => $range,
            'summary' => [
                'search_console' => $searchSummary,
                'google_analytics' => $analyticsSummary,
            ],
            'daily' => $daily,
            'pages' => $pages,
            'pages_pagination' => $pagination,
            'insights' => $insights,
            'filters' => [
                'search_run' => $searchRun === null ? '' : $searchRun->public_id,
                'analytics_run' => $analyticsRun === null ? '' : $analyticsRun->public_id,
                'from' => $range['from'],
                'to' => $range['to'],
                'page_search' => $pageSearch ?? '',
            ],
            'attribution_note' => 'Search Console and GA4 are compared by normalized landing-page path and overlapping snapshot dates. GA4 sessions include all measured traffic sources; this dashboard does not attribute GA4 sessions, engagement, key events, or revenue to Search Console clicks.',
            'candidate_note' => 'Cross-source landing-page comparison is intentionally bounded to the top 200 landing pages from each source. Use the dedicated Search Console and Google Analytics reports for exhaustive source-specific rows.',
        ];
    }

    /**
     * @return EloquentBuilder<SeoPerformanceSyncRun>
     */
    private function eligibleRuns(
        SeoPerformanceProvider $provider,
        SeoPerformanceSyncScope $scope,
    ): EloquentBuilder {
        return SeoPerformanceSyncRun::query()
            ->with('connection')
            ->where('scope', $scope->value)
            ->where('status', SeoPerformanceSyncStatus::Completed->value)
            ->whereHas(
                'connection',
                static function (EloquentBuilder $query) use ($provider): void {
                    $query->where('provider', $provider->value);
                },
            );
    }

    /**
     * @param  EloquentBuilder<SeoPerformanceSyncRun>  $eligible
     */
    private function selectedRun(
        EloquentBuilder $eligible,
        ?string $publicId,
    ): ?SeoPerformanceSyncRun {
        if ($publicId === null) {
            return (clone $eligible)
                ->latest('seo_performance_sync_runs.id')
                ->first();
        }

        return (clone $eligible)
            ->where('seo_performance_sync_runs.public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * @return array{from: string, to: string, has_overlap: bool, available_from: string, available_to: string}
     */
    private function effectiveRange(
        ?SeoPerformanceSyncRun $searchRun,
        ?SeoPerformanceSyncRun $analyticsRun,
        ?string $requestedFrom,
        ?string $requestedTo,
    ): array {
        if ($searchRun === null || $analyticsRun === null) {
            return [
                'from' => $requestedFrom ?? '',
                'to' => $requestedTo ?? '',
                'has_overlap' => false,
                'available_from' => '',
                'available_to' => '',
            ];
        }

        $availableFrom = max(
            $searchRun->date_from->format('Y-m-d'),
            $analyticsRun->date_from->format('Y-m-d'),
        );
        $availableTo = min(
            $searchRun->date_to->format('Y-m-d'),
            $analyticsRun->date_to->format('Y-m-d'),
        );

        $from = max($requestedFrom ?? $availableFrom, $availableFrom);
        $to = min($requestedTo ?? $availableTo, $availableTo);

        return [
            'from' => $from,
            'to' => $to,
            'has_overlap' => $availableFrom <= $availableTo && $from <= $to,
            'available_from' => $availableFrom,
            'available_to' => $availableTo,
        ];
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    private function searchSummary(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $row = $this->searchRows($runId, $dateFrom, $dateTo)
            ->where('dimension_type', SearchConsolePerformanceDimension::LandingPage->value)
            ->selectRaw('COALESCE(SUM(clicks), 0) AS clicks_total')
            ->selectRaw('COALESCE(SUM(impressions), 0) AS impressions_total')
            ->selectRaw('COALESCE(SUM(position * impressions), 0) AS position_weighted_total')
            ->first();

        if ($row === null) {
            return $this->emptySearchMetrics();
        }

        return $this->searchMetrics(
            clicks: (float) $row->clicks_total,
            impressions: (float) $row->impressions_total,
            positionWeighted: (float) $row->position_weighted_total,
        );
    }

    /**
     * @return array{sessions: float, engaged_sessions: float, engagement_rate: float, key_events: float, total_revenue: float}
     */
    private function analyticsSummary(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $row = $this->analyticsRows($runId, $dateFrom, $dateTo)
            ->selectRaw('COALESCE(SUM(sessions), 0) AS sessions_total')
            ->selectRaw('COALESCE(SUM(engaged_sessions), 0) AS engaged_sessions_total')
            ->selectRaw('COALESCE(SUM(key_events), 0) AS key_events_total')
            ->selectRaw('COALESCE(SUM(total_revenue), 0) AS total_revenue_total')
            ->first();

        if ($row === null) {
            return $this->emptyAnalyticsMetrics();
        }

        return $this->analyticsMetrics(
            sessions: (float) $row->sessions_total,
            engagedSessions: (float) $row->engaged_sessions_total,
            keyEvents: (float) $row->key_events_total,
            totalRevenue: (float) $row->total_revenue_total,
        );
    }

    /**
     * @return list<array<string, float|string>>
     */
    private function dailyComparison(
        int $searchRunId,
        int $analyticsRunId,
        string $dateFrom,
        string $dateTo,
    ): array {
        /** @var array<string, array<string, float|string>> $byDate */
        $byDate = [];

        $searchRows = $this->searchRows($searchRunId, $dateFrom, $dateTo)
            ->where('dimension_type', SearchConsolePerformanceDimension::LandingPage->value)
            ->select('data_date')
            ->selectRaw('SUM(clicks) AS clicks_total')
            ->selectRaw('SUM(impressions) AS impressions_total')
            ->selectRaw('SUM(position * impressions) AS position_weighted_total')
            ->groupBy('data_date')
            ->orderBy('data_date')
            ->get();

        foreach ($searchRows as $row) {
            $date = (string) $row->data_date;
            $byDate[$date] = $this->emptyDailyRow($date);
            $metrics = $this->searchMetrics(
                clicks: (float) $row->clicks_total,
                impressions: (float) $row->impressions_total,
                positionWeighted: (float) $row->position_weighted_total,
            );
            $byDate[$date] = [...$byDate[$date], ...$this->prefixMetrics('search_', $metrics)];
        }

        $analyticsRows = $this->analyticsRows($analyticsRunId, $dateFrom, $dateTo)
            ->select('data_date')
            ->selectRaw('SUM(sessions) AS sessions_total')
            ->selectRaw('SUM(engaged_sessions) AS engaged_sessions_total')
            ->selectRaw('SUM(key_events) AS key_events_total')
            ->selectRaw('SUM(total_revenue) AS total_revenue_total')
            ->groupBy('data_date')
            ->orderBy('data_date')
            ->get();

        foreach ($analyticsRows as $row) {
            $date = (string) $row->data_date;
            $byDate[$date] ??= $this->emptyDailyRow($date);
            $metrics = $this->analyticsMetrics(
                sessions: (float) $row->sessions_total,
                engagedSessions: (float) $row->engaged_sessions_total,
                keyEvents: (float) $row->key_events_total,
                totalRevenue: (float) $row->total_revenue_total,
            );
            $byDate[$date] = [...$byDate[$date], ...$this->prefixMetrics('analytics_', $metrics)];
        }

        ksort($byDate);

        return array_values($byDate);
    }

    /**
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   pagination: array{page: int, per_page: int, total: int, last_page: int},
     *   insights: list<array{type: string, level: string, path: string, message: string}>
     * }
     */
    private function pageComparison(
        int $searchRunId,
        int $analyticsRunId,
        string $dateFrom,
        string $dateTo,
        ?string $search,
        int $page,
    ): array {
        /** @var array<string, array<string, mixed>> $map */
        $map = [];

        $searchRows = $this->searchRows($searchRunId, $dateFrom, $dateTo)
            ->where('dimension_type', SearchConsolePerformanceDimension::LandingPage->value)
            ->select('dimension_value')
            ->selectRaw('SUM(clicks) AS clicks_total')
            ->selectRaw('SUM(impressions) AS impressions_total')
            ->selectRaw('SUM(position * impressions) AS position_weighted_total')
            ->groupBy('dimension_hash', 'dimension_value')
            ->orderByDesc('impressions_total')
            ->orderByDesc('clicks_total')
            ->limit(self::PAGE_CANDIDATES_PER_SOURCE)
            ->get();

        foreach ($searchRows as $row) {
            $path = $this->normalizeLandingPage((string) $row->dimension_value);
            if ($path === null) {
                continue;
            }

            $entry = $map[$path] ?? $this->emptyPageRow($path);
            $entry['search_clicks'] += (float) $row->clicks_total;
            $entry['search_impressions'] += (float) $row->impressions_total;
            $entry['search_position_weighted'] += (float) $row->position_weighted_total;
            $entry['has_search_console'] = true;
            $map[$path] = $entry;
        }

        $analyticsRows = $this->analyticsRows($analyticsRunId, $dateFrom, $dateTo)
            ->select('landing_page')
            ->selectRaw('SUM(sessions) AS sessions_total')
            ->selectRaw('SUM(engaged_sessions) AS engaged_sessions_total')
            ->selectRaw('SUM(key_events) AS key_events_total')
            ->selectRaw('SUM(total_revenue) AS total_revenue_total')
            ->groupBy('landing_page_hash', 'landing_page')
            ->orderByDesc('sessions_total')
            ->orderByDesc('engaged_sessions_total')
            ->limit(self::PAGE_CANDIDATES_PER_SOURCE)
            ->get();

        foreach ($analyticsRows as $row) {
            $path = $this->normalizeLandingPage((string) $row->landing_page);
            if ($path === null) {
                continue;
            }

            $entry = $map[$path] ?? $this->emptyPageRow($path);
            $entry['analytics_sessions'] += (float) $row->sessions_total;
            $entry['analytics_engaged_sessions'] += (float) $row->engaged_sessions_total;
            $entry['analytics_key_events'] += (float) $row->key_events_total;
            $entry['analytics_total_revenue'] += (float) $row->total_revenue_total;
            $entry['has_google_analytics'] = true;
            $map[$path] = $entry;
        }

        $rows = [];
        foreach ($map as $entry) {
            $impressions = (float) $entry['search_impressions'];
            $clicks = (float) $entry['search_clicks'];
            $sessions = (float) $entry['analytics_sessions'];
            $engaged = (float) $entry['analytics_engaged_sessions'];

            $entry['search_ctr'] = $impressions > 0 ? $clicks / $impressions : 0.0;
            $entry['search_position'] = $impressions > 0
                ? (float) $entry['search_position_weighted'] / $impressions
                : 0.0;
            $entry['analytics_engagement_rate'] = $sessions > 0 ? $engaged / $sessions : 0.0;
            unset($entry['search_position_weighted']);

            if ($search !== null && $search !== '' && ! str_contains(
                mb_strtolower((string) $entry['path']),
                mb_strtolower($search),
            )) {
                continue;
            }

            $rows[] = $entry;
        }

        usort(
            $rows,
            static function (array $left, array $right): int {
                $leftMatched = $left['has_search_console'] && $left['has_google_analytics'];
                $rightMatched = $right['has_search_console'] && $right['has_google_analytics'];

                if ($leftMatched !== $rightMatched) {
                    return $leftMatched ? -1 : 1;
                }

                $clickOrder = ((float) $right['search_clicks']) <=> ((float) $left['search_clicks']);
                if ($clickOrder !== 0) {
                    return $clickOrder;
                }

                $sessionOrder = ((float) $right['analytics_sessions']) <=> ((float) $left['analytics_sessions']);
                if ($sessionOrder !== 0) {
                    return $sessionOrder;
                }

                return strcmp((string) $left['path'], (string) $right['path']);
            },
        );

        $insights = $this->insights($rows);
        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / self::PAGES_PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        return [
            'rows' => array_slice(
                $rows,
                ($page - 1) * self::PAGES_PER_PAGE,
                self::PAGES_PER_PAGE,
            ),
            'pagination' => $this->pagination(
                page: $page,
                perPage: self::PAGES_PER_PAGE,
                total: $total,
                lastPage: $lastPage,
            ),
            'insights' => $insights,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{type: string, level: string, path: string, message: string}>
     */
    private function insights(array $rows): array
    {
        $insights = [];

        foreach ($rows as $row) {
            $path = (string) $row['path'];
            $hasSearch = (bool) $row['has_search_console'];
            $hasAnalytics = (bool) $row['has_google_analytics'];
            $impressions = (float) $row['search_impressions'];
            $clicks = (float) $row['search_clicks'];
            $ctr = (float) $row['search_ctr'];
            $sessions = (float) $row['analytics_sessions'];
            $engagementRate = (float) $row['analytics_engagement_rate'];

            if ($hasSearch && ! $hasAnalytics && ($clicks > 0 || $impressions > 0)) {
                $insights[] = [
                    'type' => 'search_console_only',
                    'level' => 'warning',
                    'path' => $path,
                    'message' => 'Search Console reports visibility for this landing page, but no matching GA4 landing-page row is present in the selected bounded snapshot.',
                ];
            } elseif (! $hasSearch && $hasAnalytics && $sessions > 0) {
                $insights[] = [
                    'type' => 'google_analytics_only',
                    'level' => 'info',
                    'path' => $path,
                    'message' => 'GA4 reports sessions for this landing page, but no matching Search Console landing-page row is present in the selected bounded snapshot.',
                ];
            }

            if ($impressions >= 100 && $ctr < 0.02) {
                $insights[] = [
                    'type' => 'high_impressions_low_ctr',
                    'level' => 'warning',
                    'path' => $path,
                    'message' => 'This page has at least 100 Search Console impressions with CTR below 2%; review title/snippet relevance before making changes.',
                ];
            }

            if (
                $hasSearch
                && $hasAnalytics
                && $clicks >= 10
                && $sessions >= 10
                && $engagementRate < 0.50
            ) {
                $insights[] = [
                    'type' => 'search_visible_low_overall_engagement',
                    'level' => 'info',
                    'path' => $path,
                    'message' => 'This search-visible page also has overall GA4 engagement below 50%. Treat this as a page-quality review signal, not as attribution of GA4 sessions to Search Console clicks.',
                ];
            }

            if (count($insights) >= self::INSIGHT_LIMIT) {
                break;
            }
        }

        return array_slice($insights, 0, self::INSIGHT_LIMIT);
    }

    private function searchRows(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        return DB::table('seo_search_console_performance_rows')
            ->where('seo_performance_sync_run_id', $runId)
            ->whereDate('data_date', '>=', $dateFrom)
            ->whereDate('data_date', '<=', $dateTo);
    }

    private function analyticsRows(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        return DB::table('seo_google_analytics_landing_page_rows')
            ->where('seo_performance_sync_run_id', $runId)
            ->whereDate('data_date', '>=', $dateFrom)
            ->whereDate('data_date', '<=', $dateTo);
    }

    private function normalizeLandingPage(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '(not set)') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $path = parse_url($value, PHP_URL_PATH);
            $query = parse_url($value, PHP_URL_QUERY);

            if (! is_string($path) || $path === '') {
                $path = '/';
            }

            return $query === null || $query === ''
                ? $path
                : $path.'?'.$query;
        }

        $fragmentPosition = strpos($value, '#');
        if ($fragmentPosition !== false) {
            $value = substr($value, 0, $fragmentPosition);
        }

        if ($value === '') {
            return '/';
        }

        return str_starts_with($value, '/') ? $value : '/'.$value;
    }

    /**
     * @param  array<string, float>  $metrics
     * @return array<string, float>
     */
    private function prefixMetrics(string $prefix, array $metrics): array
    {
        $prefixed = [];

        foreach ($metrics as $key => $value) {
            $prefixed[$prefix.$key] = $value;
        }

        return $prefixed;
    }

    /**
     * @return array<string, float|string>
     */
    private function emptyDailyRow(string $date): array
    {
        return [
            'date' => $date,
            'search_clicks' => 0.0,
            'search_impressions' => 0.0,
            'search_ctr' => 0.0,
            'search_position' => 0.0,
            'analytics_sessions' => 0.0,
            'analytics_engaged_sessions' => 0.0,
            'analytics_engagement_rate' => 0.0,
            'analytics_key_events' => 0.0,
            'analytics_total_revenue' => 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPageRow(string $path): array
    {
        return [
            'path' => $path,
            'has_search_console' => false,
            'has_google_analytics' => false,
            'search_clicks' => 0.0,
            'search_impressions' => 0.0,
            'search_position_weighted' => 0.0,
            'search_ctr' => 0.0,
            'search_position' => 0.0,
            'analytics_sessions' => 0.0,
            'analytics_engaged_sessions' => 0.0,
            'analytics_engagement_rate' => 0.0,
            'analytics_key_events' => 0.0,
            'analytics_total_revenue' => 0.0,
        ];
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    private function searchMetrics(
        float $clicks,
        float $impressions,
        float $positionWeighted,
    ): array {
        return [
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $impressions > 0 ? $clicks / $impressions : 0.0,
            'position' => $impressions > 0 ? $positionWeighted / $impressions : 0.0,
        ];
    }

    /**
     * @return array{sessions: float, engaged_sessions: float, engagement_rate: float, key_events: float, total_revenue: float}
     */
    private function analyticsMetrics(
        float $sessions,
        float $engagedSessions,
        float $keyEvents,
        float $totalRevenue,
    ): array {
        return [
            'sessions' => $sessions,
            'engaged_sessions' => $engagedSessions,
            'engagement_rate' => $sessions > 0 ? $engagedSessions / $sessions : 0.0,
            'key_events' => $keyEvents,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    private function emptySearchMetrics(): array
    {
        return [
            'clicks' => 0.0,
            'impressions' => 0.0,
            'ctr' => 0.0,
            'position' => 0.0,
        ];
    }

    /**
     * @return array{sessions: float, engaged_sessions: float, engagement_rate: float, key_events: float, total_revenue: float}
     */
    private function emptyAnalyticsMetrics(): array
    {
        return [
            'sessions' => 0.0,
            'engaged_sessions' => 0.0,
            'engagement_rate' => 0.0,
            'key_events' => 0.0,
            'total_revenue' => 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runRow(SeoPerformanceSyncRun $run): array
    {
        return [
            'public_id' => $run->public_id,
            'date_from' => $run->date_from->format('Y-m-d'),
            'date_to' => $run->date_to->format('Y-m-d'),
            'rows_persisted' => $run->rows_persisted,
            'truncated' => (bool) ($run->provider_truncated ?? false),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'connection' => $run->connection === null
                ? null
                : [
                    'display_name' => $run->connection->display_name,
                    'property_identifier' => $run->connection->property_identifier,
                    'property_url' => $run->connection->property_url,
                    'timezone' => $run->connection->timezone,
                ],
        ];
    }

    /**
     * @return array{page: int, per_page: int, total: int, last_page: int}
     */
    private function pagination(
        int $page,
        int $perPage,
        int $total,
        int $lastPage,
    ): array {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ];
    }
}
