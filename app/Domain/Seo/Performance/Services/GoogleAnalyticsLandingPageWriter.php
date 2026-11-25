<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\SeoGoogleAnalyticsLandingPageRow;
use App\Models\SeoPerformanceSyncRun;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class GoogleAnalyticsLandingPageWriter
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
                throw new DomainException('Google Analytics rows may only be written to a running synchronization.');
            }

            if ($current->scope !== SeoPerformanceSyncScope::LandingAnalytics) {
                throw new DomainException('This synchronization does not accept Google Analytics landing-page rows.');
            }

            $connection = $current->connection;
            if ($connection === null || $connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
                throw new DomainException('This synchronization is not attached to Google Analytics.');
            }

            if ($page->rows === []) {
                return 0;
            }

            $from = $current->date_from->format('Y-m-d');
            $to = $current->date_to->format('Y-m-d');
            $now = now();
            $records = [];

            foreach ($page->rows as $row) {
                $date = $row['date'] ?? null;
                $landingPage = $row['landing_page'] ?? null;
                $sessions = $row['sessions'] ?? null;
                $engagedSessions = $row['engaged_sessions'] ?? null;
                $activeUsers = $row['active_users'] ?? null;
                $newUsers = $row['new_users'] ?? null;
                $keyEvents = $row['key_events'] ?? null;
                $totalRevenue = $row['total_revenue'] ?? null;

                if (
                    ! is_string($date)
                    || ! $this->isValidDate($date)
                    || $date < $from
                    || $date > $to
                    || ! is_string($landingPage)
                    || trim($landingPage) === ''
                    || ! is_numeric($sessions)
                    || ! is_numeric($engagedSessions)
                    || ! is_numeric($activeUsers)
                    || ! is_numeric($newUsers)
                    || ! is_numeric($keyEvents)
                    || ! is_numeric($totalRevenue)
                ) {
                    throw new DomainException('A Google Analytics landing-page row is invalid for this synchronization.');
                }

                $sessionsValue = (float) $sessions;
                $engagedSessionsValue = (float) $engagedSessions;
                $activeUsersValue = (float) $activeUsers;
                $newUsersValue = (float) $newUsers;
                $keyEventsValue = (float) $keyEvents;
                $totalRevenueValue = (float) $totalRevenue;

                if (
                    ! is_finite($sessionsValue)
                    || ! is_finite($engagedSessionsValue)
                    || ! is_finite($activeUsersValue)
                    || ! is_finite($newUsersValue)
                    || ! is_finite($keyEventsValue)
                    || ! is_finite($totalRevenueValue)
                    || $sessionsValue < 0
                    || $engagedSessionsValue < 0
                    || $activeUsersValue < 0
                    || $newUsersValue < 0
                    || $keyEventsValue < 0
                ) {
                    throw new DomainException('A Google Analytics landing-page row contains invalid metrics.');
                }

                $landingPage = trim($landingPage);
                $records[] = [
                    'seo_performance_sync_run_id' => $current->getKey(),
                    'seo_performance_connection_id' => $connection->getKey(),
                    'data_date' => $date,
                    'landing_page' => $landingPage,
                    'landing_page_hash' => hash('sha256', $landingPage),
                    'sessions' => number_format($sessionsValue, 4, '.', ''),
                    'engaged_sessions' => number_format($engagedSessionsValue, 4, '.', ''),
                    'active_users' => number_format($activeUsersValue, 4, '.', ''),
                    'new_users' => number_format($newUsersValue, 4, '.', ''),
                    'key_events' => number_format($keyEventsValue, 4, '.', ''),
                    'total_revenue' => number_format($totalRevenueValue, 6, '.', ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            SeoGoogleAnalyticsLandingPageRow::query()->upsert(
                $records,
                [
                    'seo_performance_sync_run_id',
                    'data_date',
                    'landing_page_hash',
                ],
                [
                    'landing_page',
                    'sessions',
                    'engaged_sessions',
                    'active_users',
                    'new_users',
                    'key_events',
                    'total_revenue',
                    'updated_at',
                ],
            );

            return count($records);
        });
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
