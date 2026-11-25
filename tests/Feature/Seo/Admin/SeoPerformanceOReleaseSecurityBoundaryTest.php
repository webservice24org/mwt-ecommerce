<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Models\Admin;
use App\Models\SeoPerformanceConnection;
use App\Models\SeoPerformanceSyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoPerformanceOReleaseSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_performance_reporting_workspaces_require_admin_authentication(): void
    {
        $this->get(route('admin.seo.performance.index'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.seo.performance.search-console.index'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.seo.performance.google-analytics.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_unified_dashboard_rejects_cross_provider_public_ids(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $searchRun = $this->completedRun(SeoPerformanceProvider::SearchConsole);
        $analyticsRun = $this->completedRun(SeoPerformanceProvider::GoogleAnalytics);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.index', [
                'search_run' => $analyticsRun->public_id,
                'analytics_run' => $searchRun->public_id,
            ]))
            ->assertNotFound();
    }

    public function test_unified_dashboard_rejects_non_completed_snapshots_even_when_public_id_is_valid(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);
        $searchRun = $this->completedRun(SeoPerformanceProvider::SearchConsole);
        $analyticsRun = $this->completedRun(SeoPerformanceProvider::GoogleAnalytics);

        $analyticsRun->forceFill([
            'status' => SeoPerformanceSyncStatus::Running,
            'completed_at' => null,
        ])->save();

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.index', [
                'search_run' => $searchRun->public_id,
                'analytics_run' => $analyticsRun->public_id,
            ]))
            ->assertNotFound();
    }

    private function completedRun(SeoPerformanceProvider $provider): SeoPerformanceSyncRun
    {
        $connection = SeoPerformanceConnection::query()->create([
            'public_id' => Str::uuid()->toString(),
            'provider' => $provider,
            'status' => SeoPerformanceConnectionStatus::Active,
            'display_name' => $provider === SeoPerformanceProvider::SearchConsole
                ? 'Primary Search Console'
                : 'Primary GA4',
            'property_identifier' => $provider === SeoPerformanceProvider::SearchConsole
                ? 'sc-domain:example.com'
                : '123456789',
            'property_url' => 'https://example.com/',
            'timezone' => 'UTC',
            'capabilities' => $provider === SeoPerformanceProvider::SearchConsole
                ? ['search_queries', 'search_landing_pages']
                : ['analytics_landing_pages'],
            'verified_at' => now(),
        ]);

        $scope = $provider === SeoPerformanceProvider::SearchConsole
            ? SeoPerformanceSyncScope::SearchPerformance
            : SeoPerformanceSyncScope::LandingAnalytics;

        return SeoPerformanceSyncRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'seo_performance_connection_id' => $connection->id,
            'scope' => $scope,
            'mode' => SeoPerformanceSyncMode::Manual,
            'status' => SeoPerformanceSyncStatus::Completed,
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-21',
            'attempt_count' => 1,
            'rows_received' => 0,
            'rows_persisted' => 0,
            'provider_complete' => true,
            'provider_truncated' => false,
            'queue_pass_count' => 1,
            'orchestration_version' => $provider === SeoPerformanceProvider::SearchConsole
                ? 'search-console-sync-v1'
                : 'google-analytics-sync-v1',
            'completed_at' => now(),
        ]);
    }
}
