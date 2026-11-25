<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Enums\SearchConsolePerformanceDimension;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\SeoPerformanceSyncRun;
use App\Models\SeoSearchConsolePerformanceRow;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SearchConsolePerformancePageWriter
{
    public function persist(
        SeoPerformanceSyncRun $run,
        SeoPerformanceFetchPageData $page,
    ): int {
        return DB::transaction(function () use ($run, $page): int {
            $current = SeoPerformanceSyncRun::query()
                ->with('connection')
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoPerformanceSyncStatus::Running) {
                throw new DomainException('Search Console rows may only be written to a running synchronization.');
            }

            if ($current->scope !== SeoPerformanceSyncScope::SearchPerformance) {
                throw new DomainException('This synchronization does not accept Search Console performance rows.');
            }

            $connection = $current->connection;
            if ($connection === null || $connection->provider !== SeoPerformanceProvider::SearchConsole) {
                throw new DomainException('This synchronization is not attached to Google Search Console.');
            }

            if ($page->rows === []) {
                return 0;
            }

            $from = $current->date_from->format('Y-m-d');
            $to = $current->date_to->format('Y-m-d');
            $now = now();
            $records = [];

            foreach ($page->rows as $row) {
                $dimension = isset($row['dimension_type']) && is_string($row['dimension_type'])
                    ? SearchConsolePerformanceDimension::tryFrom($row['dimension_type'])
                    : null;
                $searchType = $row['search_type'] ?? null;
                $date = $row['date'] ?? null;
                $value = $row['dimension_value'] ?? null;
                $clicks = $row['clicks'] ?? null;
                $impressions = $row['impressions'] ?? null;
                $ctr = $row['ctr'] ?? null;
                $position = $row['position'] ?? null;

                if (
                    $dimension === null
                    || $searchType !== 'web'
                    || ! is_string($date)
                    || ! $this->isValidDate($date)
                    || $date < $from
                    || $date > $to
                    || ! is_string($value)
                    || trim($value) === ''
                    || ! is_numeric($clicks)
                    || ! is_numeric($impressions)
                    || ! is_numeric($ctr)
                    || ! is_numeric($position)
                ) {
                    throw new DomainException('A Search Console performance row is invalid for this synchronization.');
                }

                $clicksValue = (float) $clicks;
                $impressionsValue = (float) $impressions;
                $ctrValue = (float) $ctr;
                $positionValue = (float) $position;

                if (
                    $clicksValue < 0
                    || $impressionsValue < 0
                    || $ctrValue < 0
                    || $ctrValue > 1
                    || $positionValue < 0
                ) {
                    throw new DomainException('A Search Console performance row contains invalid metrics.');
                }

                $records[] = [
                    'seo_performance_sync_run_id' => $current->getKey(),
                    'seo_performance_connection_id' => $connection->getKey(),
                    'dimension_type' => $dimension->value,
                    'search_type' => 'web',
                    'data_date' => $date,
                    'dimension_value' => $value,
                    'dimension_hash' => hash('sha256', $value),
                    'clicks' => number_format($clicksValue, 4, '.', ''),
                    'impressions' => number_format($impressionsValue, 4, '.', ''),
                    'ctr' => number_format($ctrValue, 10, '.', ''),
                    'position' => number_format($positionValue, 4, '.', ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            SeoSearchConsolePerformanceRow::query()->upsert(
                $records,
                [
                    'seo_performance_sync_run_id',
                    'dimension_type',
                    'data_date',
                    'dimension_hash',
                ],
                [
                    'dimension_value',
                    'clicks',
                    'impressions',
                    'ctr',
                    'position',
                    'updated_at',
                ],
            );

            return count($records);
        });
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
