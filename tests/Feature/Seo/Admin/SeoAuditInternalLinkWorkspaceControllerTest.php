<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoAuditInternalLinkWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_editor_can_open_internal_link_workspace(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.seo.internal-links.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('Admin/Seo/InternalLinks/Index')
                    ->has('workspace.summary')
                    ->has('workspace.opportunities'),
            );
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this
            ->get(route('admin.seo.internal-links.index'))
            ->assertRedirect(route('admin.login'));
    }
}
