<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Queries;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class GetGoogleAnalyticsPerformanceReportQuery
{
    private const RUNS_PER_PAGE = 15;

    private const LANDING_PAGES_PER_PAGE = 25;

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $runPublicId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $pageSearch,
        int $runsPage,
        int $pagesPage,
    ): array {
        $runsPage = max(1, $runsPage);
        $pagesPage = max(1, $pagesPage);

        $eligible = $this->eligibleRuns();
        $selectedRun = $runPublicId === null
            ? (clone $eligible)->latest('seo_performance_sync_runs.id')->first()
            : (clone $eligible)
                ->where('seo_performance_sync_runs.public_id', $runPublicId)
                ->firstOrFail();

        $runsTotal = (clone $eligible)->count();
        $runsLastPage = max(1, (int) ceil($runsTotal / self::RUNS_PER_PAGE));
        $runsPage = min($runsPage, $runsLastPage);

        $runs = (clone $eligible)
            ->latest('seo_performance_sync_runs.id')
            ->skip(($runsPage - 1) * self::RUNS_PER_PAGE)
            ->take(self::RUNS_PER_PAGE)
            ->get()
            ->map(fn (SeoPerformanceSyncRun $run): array => $this->runRow($run))
            ->values()
            ->all();

        if ($selectedRun === null) {
            return $this->emptyWorkspace(
                runs: $runs,
                runsPage: $runsPage,
                runsTotal: $runsTotal,
                runsLastPage: $runsLastPage,
                runPublicId: $runPublicId,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                pageSearch: $pageSearch,
            );
        }

        $runFrom = $selectedRun->date_from->format('Y-m-d');
        $runTo = $selectedRun->date_to->format('Y-m-d');
        $effectiveFrom = $dateFrom ?? $runFrom;
        $effectiveTo = $dateTo ?? $runTo;

        $summary = $this->summary(
            runId: $selectedRun->getKey(),
            dateFrom: $effectiveFrom,
            dateTo: $effectiveTo,
        );
        $daily = $this->dailyTrend(
            runId: $selectedRun->getKey(),
            dateFrom: $effectiveFrom,
            dateTo: $effectiveTo,
        );
        $pages = $this->landingPageReport(
            runId: $selectedRun->getKey(),
            dateFrom: $effectiveFrom,
            dateTo: $effectiveTo,
            search: $pageSearch,
            page: $pagesPage,
        );

        return [
            'runs' => $runs,
            'runs_pagination' => $this->pagination(
                page: $runsPage,
                perPage: self::RUNS_PER_PAGE,
                total: $runsTotal,
                lastPage: $runsLastPage,
            ),
            'selected_run' => $this->runRow($selectedRun),
            'summary' => $summary,
            'daily' => $daily,
            'pages' => $pages['rows'],
            'pages_pagination' => $pages['pagination'],
            'filters' => [
                'run' => $selectedRun->public_id,
                'from' => $effectiveFrom,
                'to' => $effectiveTo,
                'page_search' => $pageSearch ?? '',
            ],
            'dataset_note' => 'Summary metrics are additive GA4 landing-page metrics from one completed sync snapshot. Active and new users are shown only as landing-page row sums because distinct-user metrics are not safely additive across rows or dates.',
        ];
    }

    /**
     * @return EloquentBuilder<SeoPerformanceSyncRun>
     */
    private function eligibleRuns(): EloquentBuilder
    {
        return SeoPerformanceSyncRun::query()
            ->with('connection')
            ->where('scope', SeoPerformanceSyncScope::LandingAnalytics->value)
            ->where('status', SeoPerformanceSyncStatus::Completed->value)
            ->whereHas(
                'connection',
                static function (EloquentBuilder $query): void {
                    $query->where(
                        'provider',
                        SeoPerformanceProvider::GoogleAnalytics->value,
                    );
                },
            );
    }

    /**
     * @return array{
     *     sessions: float,
     *     engaged_sessions: float,
     *     engagement_rate: float,
     *     key_events: float,
     *     total_revenue: float
     * }
     */
    private function summary(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $row = $this->baseRows(
            runId: $runId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        )
            ->selectRaw('COALESCE(SUM(sessions), 0) AS sessions_total')
            ->selectRaw('COALESCE(SUM(engaged_sessions), 0) AS engaged_sessions_total')
            ->selectRaw('COALESCE(SUM(key_events), 0) AS key_events_total')
            ->selectRaw('COALESCE(SUM(total_revenue), 0) AS total_revenue_total')
            ->first();

        return $this->metrics(
            sessions: (float) $row->sessions_total,
            engagedSessions: (float) $row->engaged_sessions_total,
            keyEvents: (float) $row->key_events_total,
            totalRevenue: (float) $row->total_revenue_total,
        );
    }

    /**
     * @return list<array{
     *     date: string,
     *     sessions: float,
     *     engaged_sessions: float,
     *     engagement_rate: float,
     *     key_events: float,
     *     total_revenue: float
     * }>
     */
    private function dailyTrend(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        return $this->baseRows(
            runId: $runId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        )
            ->select('data_date')
            ->selectRaw('SUM(sessions) AS sessions_total')
            ->selectRaw('SUM(engaged_sessions) AS engaged_sessions_total')
            ->selectRaw('SUM(key_events) AS key_events_total')
            ->selectRaw('SUM(total_revenue) AS total_revenue_total')
            ->groupBy('data_date')
            ->orderBy('data_date')
            ->get()
            ->map(function (object $row): array {
                return [
                    'date' => (string) $row->data_date,
                    ...$this->metrics(
                        sessions: (float) $row->sessions_total,
                        engagedSessions: (float) $row->engaged_sessions_total,
                        keyEvents: (float) $row->key_events_total,
                        totalRevenue: (float) $row->total_revenue_total,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   rows: list<array{
     *       landing_page: string,
     *       sessions: float,
     *       engaged_sessions: float,
     *       engagement_rate: float,
     *       active_users_row_sum: float,
     *       new_users_row_sum: float,
     *       key_events: float,
     *       total_revenue: float
     *   }>,
     *   pagination: array{page: int, per_page: int, total: int, last_page: int}
     * }
     */
    private function landingPageReport(
        int $runId,
        string $dateFrom,
        string $dateTo,
        ?string $search,
        int $page,
    ): array {
        $base = $this->baseRows(
            runId: $runId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        if ($search !== null && $search !== '') {
            $base->where(
                'landing_page',
                'like',
                '%'.addcslashes($search, '\\%_').'%',
            );
        }

        $total = (clone $base)
            ->distinct()
            ->count('landing_page_hash');
        $lastPage = max(1, (int) ceil($total / self::LANDING_PAGES_PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        $rows = (clone $base)
            ->select(['landing_page_hash', 'landing_page'])
            ->selectRaw('SUM(sessions) AS sessions_total')
            ->selectRaw('SUM(engaged_sessions) AS engaged_sessions_total')
            ->selectRaw('SUM(active_users) AS active_users_row_sum')
            ->selectRaw('SUM(new_users) AS new_users_row_sum')
            ->selectRaw('SUM(key_events) AS key_events_total')
            ->selectRaw('SUM(total_revenue) AS total_revenue_total')
            ->groupBy('landing_page_hash', 'landing_page')
            ->orderByDesc('sessions_total')
            ->orderByDesc('engaged_sessions_total')
            ->orderBy('landing_page')
            ->skip(($page - 1) * self::LANDING_PAGES_PER_PAGE)
            ->take(self::LANDING_PAGES_PER_PAGE)
            ->get()
            ->map(function (object $row): array {
                return [
                    'landing_page' => (string) $row->landing_page,
                    ...$this->metrics(
                        sessions: (float) $row->sessions_total,
                        engagedSessions: (float) $row->engaged_sessions_total,
                        keyEvents: (float) $row->key_events_total,
                        totalRevenue: (float) $row->total_revenue_total,
                    ),
                    'active_users_row_sum' => (float) $row->active_users_row_sum,
                    'new_users_row_sum' => (float) $row->new_users_row_sum,
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'pagination' => $this->pagination(
                page: $page,
                perPage: self::LANDING_PAGES_PER_PAGE,
                total: $total,
                lastPage: $lastPage,
            ),
        ];
    }

    private function baseRows(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        return DB::table('seo_google_analytics_landing_page_rows')
            ->where('seo_performance_sync_run_id', $runId)
            ->whereDate('data_date', '>=', $dateFrom)
            ->whereDate('data_date', '<=', $dateTo);
    }

    /**
     * @return array{
     *     sessions: float,
     *     engaged_sessions: float,
     *     engagement_rate: float,
     *     key_events: float,
     *     total_revenue: float
     * }
     */
    private function metrics(
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
     * @return array<string, mixed>
     */
    private function runRow(SeoPerformanceSyncRun $run): array
    {
        $connection = $run->connection;

        return [
            'public_id' => $run->public_id,
            'date_from' => $run->date_from->format('Y-m-d'),
            'date_to' => $run->date_to->format('Y-m-d'),
            'rows_received' => $run->rows_received,
            'rows_persisted' => $run->rows_persisted,
            'truncated' => $run->provider_truncated,
            'queue_passes' => $run->queue_pass_count,
            'completed_at' => $run->completed_at?->toIso8601String(),
            'connection' => $connection instanceof SeoPerformanceConnection
                ? [
                    'public_id' => $connection->public_id,
                    'display_name' => $connection->display_name,
                    'property_identifier' => $connection->property_identifier,
                    'property_url' => $connection->property_url,
                    'timezone' => $connection->timezone,
                ]
                : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $runs
     * @return array<string, mixed>
     */
    private function emptyWorkspace(
        array $runs,
        int $runsPage,
        int $runsTotal,
        int $runsLastPage,
        ?string $runPublicId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $pageSearch,
    ): array {
        return [
            'runs' => $runs,
            'runs_pagination' => $this->pagination(
                page: $runsPage,
                perPage: self::RUNS_PER_PAGE,
                total: $runsTotal,
                lastPage: $runsLastPage,
            ),
            'selected_run' => null,
            'summary' => $this->metrics(0.0, 0.0, 0.0, 0.0),
            'daily' => [],
            'pages' => [],
            'pages_pagination' => $this->pagination(1, self::LANDING_PAGES_PER_PAGE, 0, 1),
            'filters' => [
                'run' => $runPublicId ?? '',
                'from' => $dateFrom ?? '',
                'to' => $dateTo ?? '',
                'page_search' => $pageSearch ?? '',
            ],
            'dataset_note' => 'Summary metrics are additive GA4 landing-page metrics from one completed sync snapshot. Active and new users are shown only as landing-page row sums because distinct-user metrics are not safely additive across rows or dates.',
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
