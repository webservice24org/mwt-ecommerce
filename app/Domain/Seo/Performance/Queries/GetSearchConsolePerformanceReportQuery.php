<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Queries;

use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class GetSearchConsolePerformanceReportQuery
{
    private const RUNS_PER_PAGE = 15;

    private const DIMENSIONS_PER_PAGE = 20;

    /**
     * @return array<string, mixed>
     */
    public function get(
        ?string $runPublicId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $querySearch,
        ?string $pageSearch,
        int $runsPage,
        int $queriesPage,
        int $pagesPage,
    ): array {
        $runsPage = max(1, $runsPage);
        $queriesPage = max(1, $queriesPage);
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
                querySearch: $querySearch,
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
        $queries = $this->dimensionReport(
            runId: $selectedRun->getKey(),
            dimension: SearchConsolePerformanceDimension::Query,
            dateFrom: $effectiveFrom,
            dateTo: $effectiveTo,
            search: $querySearch,
            page: $queriesPage,
        );
        $pages = $this->dimensionReport(
            runId: $selectedRun->getKey(),
            dimension: SearchConsolePerformanceDimension::LandingPage,
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
            'queries' => $queries['rows'],
            'queries_pagination' => $queries['pagination'],
            'pages' => $pages['rows'],
            'pages_pagination' => $pages['pagination'],
            'filters' => [
                'run' => $selectedRun->public_id,
                'from' => $effectiveFrom,
                'to' => $effectiveTo,
                'query_search' => $querySearch ?? '',
                'page_search' => $pageSearch ?? '',
            ],
            'dataset_note' => 'Summary and daily totals use landing-page rows. Query rows are ranked separately so the same Search Console metrics are never summed across dimensions.',
        ];
    }

    /**
     * @return EloquentBuilder<SeoPerformanceSyncRun>
     */
    private function eligibleRuns(): EloquentBuilder
    {
        return SeoPerformanceSyncRun::query()
            ->with('connection')
            ->where('scope', SeoPerformanceSyncScope::SearchPerformance->value)
            ->where('status', SeoPerformanceSyncStatus::Completed->value)
            ->whereHas(
                'connection',
                static function (EloquentBuilder $query): void {
                    $query->where(
                        'provider',
                        SeoPerformanceProvider::SearchConsole->value,
                    );
                },
            );
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    private function summary(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $row = $this->baseRows(
            runId: $runId,
            dimension: SearchConsolePerformanceDimension::LandingPage,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        )
            ->selectRaw('COALESCE(SUM(clicks), 0) AS clicks_total')
            ->selectRaw('COALESCE(SUM(impressions), 0) AS impressions_total')
            ->selectRaw('COALESCE(SUM(position * impressions), 0) AS position_weighted_total')
            ->first();

        return $this->metrics(
            clicks: (float) $row->clicks_total,
            impressions: (float) $row->impressions_total,
            positionWeighted: (float) $row->position_weighted_total,
        );
    }

    /**
     * @return list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>
     */
    private function dailyTrend(
        int $runId,
        string $dateFrom,
        string $dateTo,
    ): array {
        return $this->baseRows(
            runId: $runId,
            dimension: SearchConsolePerformanceDimension::LandingPage,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        )
            ->select('data_date')
            ->selectRaw('SUM(clicks) AS clicks_total')
            ->selectRaw('SUM(impressions) AS impressions_total')
            ->selectRaw('SUM(position * impressions) AS position_weighted_total')
            ->groupBy('data_date')
            ->orderBy('data_date')
            ->get()
            ->map(function (object $row): array {
                $metrics = $this->metrics(
                    clicks: (float) $row->clicks_total,
                    impressions: (float) $row->impressions_total,
                    positionWeighted: (float) $row->position_weighted_total,
                );

                return [
                    'date' => (string) $row->data_date,
                    ...$metrics,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   rows: list<array{value: string, clicks: float, impressions: float, ctr: float, position: float}>,
     *   pagination: array{page: int, per_page: int, total: int, last_page: int}
     * }
     */
    private function dimensionReport(
        int $runId,
        SearchConsolePerformanceDimension $dimension,
        string $dateFrom,
        string $dateTo,
        ?string $search,
        int $page,
    ): array {
        $base = $this->baseRows(
            runId: $runId,
            dimension: $dimension,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        if ($search !== null && $search !== '') {
            $base->where(
                'dimension_value',
                'like',
                '%'.addcslashes($search, '\\%_').'%',
            );
        }

        $total = (clone $base)
            ->distinct()
            ->count('dimension_hash');
        $lastPage = max(1, (int) ceil($total / self::DIMENSIONS_PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        $rows = (clone $base)
            ->select(['dimension_hash', 'dimension_value'])
            ->selectRaw('SUM(clicks) AS clicks_total')
            ->selectRaw('SUM(impressions) AS impressions_total')
            ->selectRaw('SUM(position * impressions) AS position_weighted_total')
            ->groupBy('dimension_hash', 'dimension_value')
            ->orderByDesc('clicks_total')
            ->orderByDesc('impressions_total')
            ->orderBy('dimension_value')
            ->skip(($page - 1) * self::DIMENSIONS_PER_PAGE)
            ->take(self::DIMENSIONS_PER_PAGE)
            ->get()
            ->map(function (object $row): array {
                $metrics = $this->metrics(
                    clicks: (float) $row->clicks_total,
                    impressions: (float) $row->impressions_total,
                    positionWeighted: (float) $row->position_weighted_total,
                );

                return [
                    'value' => (string) $row->dimension_value,
                    ...$metrics,
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'pagination' => $this->pagination(
                page: $page,
                perPage: self::DIMENSIONS_PER_PAGE,
                total: $total,
                lastPage: $lastPage,
            ),
        ];
    }

    private function baseRows(
        int $runId,
        SearchConsolePerformanceDimension $dimension,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        return DB::table('seo_search_console_performance_rows')
            ->where('seo_performance_sync_run_id', $runId)
            ->where('dimension_type', $dimension->value)
            ->whereDate('data_date', '>=', $dateFrom)
            ->whereDate('data_date', '<=', $dateTo);
    }

    /**
     * @return array{clicks: float, impressions: float, ctr: float, position: float}
     */
    private function metrics(
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
        ?string $querySearch,
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
            'summary' => $this->metrics(0.0, 0.0, 0.0),
            'daily' => [],
            'queries' => [],
            'queries_pagination' => $this->pagination(1, self::DIMENSIONS_PER_PAGE, 0, 1),
            'pages' => [],
            'pages_pagination' => $this->pagination(1, self::DIMENSIONS_PER_PAGE, 0, 1),
            'filters' => [
                'run' => $runPublicId ?? '',
                'from' => $dateFrom ?? '',
                'to' => $dateTo ?? '',
                'query_search' => $querySearch ?? '',
                'page_search' => $pageSearch ?? '',
            ],
            'dataset_note' => 'Summary and daily totals use landing-page rows. Query rows are ranked separately so the same Search Console metrics are never summed across dimensions.',
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
