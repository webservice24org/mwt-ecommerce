<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_role_is_cast_correctly(): void
    {
        $admin = Admin::factory()
            ->superAdmin()
            ->create();

        $this->assertSame(
            AdminRole::SuperAdmin,
            $admin->role,
        );

        $this->assertTrue(
            $admin->isSuperAdmin(),
        );
    }

    public function test_normal_admin_is_not_super_admin(): void
    {
        $admin = Admin::factory()->create();

        $this->assertFalse(
            $admin->isSuperAdmin(),
        );
    }
}
