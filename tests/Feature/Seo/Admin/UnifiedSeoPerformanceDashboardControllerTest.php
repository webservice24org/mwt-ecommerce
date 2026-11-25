<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class UnifiedSeoPerformanceDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_editor_can_view_unified_performance_workspace(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Editor]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Seo/Performance/Overview')
                    ->has('workspace.summary.search_console')
                    ->has('workspace.summary.google_analytics')
                    ->has('workspace.attribution_note'),
            );
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this
            ->get(route('admin.seo.performance.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_invalid_date_filter_is_rejected(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.performance.index', [
                'from' => 'bad-date',
            ]))
            ->assertSessionHasErrors('from');
    }
}
