<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(
            route('admin.dashboard'),
        );

        $response->assertRedirect(
            route('admin.login'),
        );
    }

    public function test_active_admin_can_login(): void
    {
        $admin = Admin::factory()->create([
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post(
            route('admin.login.store'),
            [
                'email' => $admin->email,
                'password' => 'password123',
            ],
        );

        $response->assertRedirect(
            route('admin.dashboard'),
        );

        $this->assertAuthenticatedAs(
            $admin,
            'admin',
        );
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $admin = Admin::factory()->create([
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->post(
            route('admin.login.store'),
            [
                'email' => $admin->email,
                'password' => 'password123',
            ],
        );

        $this->assertGuest('admin');
    }

    public function test_inactive_authenticated_admin_is_logged_out(): void
    {
        $admin = Admin::factory()->create([
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertRedirect(
            route('admin.login'),
        );

        $this->assertGuest('admin');
    }
}
