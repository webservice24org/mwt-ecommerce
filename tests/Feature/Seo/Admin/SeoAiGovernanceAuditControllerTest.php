<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoAiGovernanceAuditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_editor_can_view_read_only_governance_workspace(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Editor]);
        $run = app(SeoAiGovernanceManager::class)->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-v4',
            input: ['title' => 'USB Cable'],
            requester: $admin,
        );

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.ai-governance.index', ['run' => $run->public_id]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Seo/AiGovernance/Index')
                    ->where('workspace.selected_run.public_id', $run->public_id)
                    ->where('workspace.selected_run.provider', 'provider-a')
                    ->has('workspace.workspace_note'),
            );
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this
            ->get(route('admin.seo.ai-governance.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_invalid_enum_filter_is_rejected(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.ai-governance.index', ['status' => 'not-a-real-status']))
            ->assertSessionHasErrors('status');
    }
}
