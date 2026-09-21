<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Page;
use App\Policies\PagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_all_page_abilities(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::SuperAdmin,
        ]);

        $page = Page::factory()->create();

        $policy = new PagePolicy;

        $this->assertTrue(
            $policy->before(
                $admin,
                'viewAny',
            ),
        );

        $this->assertTrue(
            $policy->before(
                $admin,
                'delete',
            ),
        );
    }

    public function test_admin_can_manage_and_delete_pages(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $page = Page::factory()->create();

        $policy = new PagePolicy;

        $this->assertTrue(
            $policy->viewAny($admin),
        );

        $this->assertTrue(
            $policy->create($admin),
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $page,
            ),
        );

        $this->assertTrue(
            $policy->delete(
                $admin,
                $page,
            ),
        );
    }

    public function test_manager_can_manage_and_delete_pages(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $page = Page::factory()->create();

        $policy = new PagePolicy;

        $this->assertTrue(
            $policy->viewAny($admin),
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $page,
            ),
        );

        $this->assertTrue(
            $policy->delete(
                $admin,
                $page,
            ),
        );
    }

    public function test_editor_can_manage_but_cannot_delete_pages(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $policy = new PagePolicy;

        $this->assertTrue(
            $policy->viewAny($admin),
        );

        $this->assertTrue(
            $policy->create($admin),
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $page,
            ),
        );

        $this->assertFalse(
            $policy->delete(
                $admin,
                $page,
            ),
        );
    }
}
