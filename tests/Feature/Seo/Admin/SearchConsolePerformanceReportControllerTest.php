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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SearchConsolePerformanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_editor_can_view_completed_search_console_reporting_snapshot(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Editor]);
        $run = $this->makeRun();

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.search-console.index', [
                'run' => $run->public_id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Seo/Performance/SearchConsole')
                    ->where('workspace.selected_run.public_id', $run->public_id)
                    ->where('workspace.selected_run.connection.property_identifier', 'sc-domain:example.com')
                    ->has('workspace.dataset_note'),
            );
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this
            ->get(route('admin.seo.performance.search-console.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_invalid_date_filter_is_rejected(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.search-console.index', [
                'from' => 'not-a-date',
            ]))
            ->assertSessionHasErrors('from');
    }

    private function makeRun(): SeoPerformanceSyncRun
    {
        $connection = SeoPerformanceConnection::query()->create([
            'public_id' => Str::uuid()->toString(),
            'provider' => SeoPerformanceProvider::SearchConsole,
            'status' => SeoPerformanceConnectionStatus::Active,
            'display_name' => 'Primary Search Console',
            'property_identifier' => 'sc-domain:example.com',
            'property_url' => 'https://example.com/',
            'timezone' => 'UTC',
            'capabilities' => ['search_queries', 'search_landing_pages'],
            'verified_at' => now(),
        ]);

        return SeoPerformanceSyncRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'seo_performance_connection_id' => $connection->id,
            'scope' => SeoPerformanceSyncScope::SearchPerformance,
            'mode' => SeoPerformanceSyncMode::Manual,
            'status' => SeoPerformanceSyncStatus::Completed,
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-21',
            'attempt_count' => 1,
            'rows_received' => 0,
            'rows_persisted' => 0,
            'provider_complete' => true,
            'provider_truncated' => false,
            'queue_pass_count' => 2,
            'orchestration_version' => 'search-console-sync-v1',
            'completed_at' => now(),
        ]);
    }
}
