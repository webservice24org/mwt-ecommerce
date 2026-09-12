<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_admin_cannot_access_admin_management(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_admin_management(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_editor_cannot_access_admin_management(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_admin_management(): void
    {
        $admin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_an_administrator(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Store Manager',
                'email' => 'manager@example.com',
                'password' => 'Manager@12345',
                'password_confirmation' => 'Manager@12345',
                'role' => AdminRole::Manager->value,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('admins', [
            'name' => 'Store Manager',
            'email' => 'manager@example.com',
            'role' => AdminRole::Manager->value,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_admin_email_is_rejected(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        Admin::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Another Admin',
                'email' => 'existing@example.com',
                'password' => 'AdminPass@12345',
                'password_confirmation' => 'AdminPass@12345',
                'role' => AdminRole::Admin->value,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_admin_role_is_rejected(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Invalid Role Admin',
                'email' => 'invalid-role@example.com',
                'password' => 'AdminPass@12345',
                'password_confirmation' => 'AdminPass@12345',
                'role' => 'owner',
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('admins', [
            'email' => 'invalid-role@example.com',
        ]);
    }

    public function test_super_admin_can_update_an_administrator_without_changing_password(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $admin = Admin::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'Original@12345',
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);

        $oldPassword = $admin->password;

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->put(route('admin.admins.update', $admin), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => AdminRole::Manager->value,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $admin->refresh();

        $this->assertSame('Updated Name', $admin->name);
        $this->assertSame('updated@example.com', $admin->email);
        $this->assertSame(AdminRole::Manager, $admin->role);
        $this->assertSame($oldPassword, $admin->password);
    }

    public function test_super_admin_can_change_an_administrator_password(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $admin = Admin::factory()->create([
            'password' => 'Original@12345',
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->put(route('admin.admins.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'Changed@12345',
                'password_confirmation' => 'Changed@12345',
                'role' => AdminRole::Admin->value,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $this->assertTrue(
            password_verify(
                'Changed@12345',
                $admin->fresh()->password,
            ),
        );
    }

    public function test_super_admin_can_delete_another_administrator(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $admin = Admin::factory()->create();

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->delete(route('admin.admins.destroy', $admin));

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('admins', [
            'id' => $admin->id,
        ]);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->delete(route('admin.admins.destroy', $superAdmin));

        $response
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('admins', [
            'id' => $superAdmin->id,
        ]);
    }

    public function test_final_active_super_admin_cannot_be_demoted(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->put(route('admin.admins.update', $superAdmin), [
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => AdminRole::Admin->value,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('error');

        $superAdmin->refresh();

        $this->assertSame(
            AdminRole::SuperAdmin,
            $superAdmin->role,
        );
    }

    public function test_final_active_super_admin_cannot_be_deactivated(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->put(route('admin.admins.update', $superAdmin), [
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => AdminRole::SuperAdmin->value,
                'is_active' => false,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('error');

        $superAdmin->refresh();

        $this->assertTrue($superAdmin->is_active);
    }

    public function test_final_active_super_admin_cannot_be_deleted(): void
    {
        $superAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $otherAdmin = Admin::factory()->create([
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($superAdmin, 'admin')
            ->delete(route('admin.admins.destroy', $superAdmin));

        $response
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('admins', [
            'id' => $superAdmin->id,
        ]);

        $this->assertDatabaseHas('admins', [
            'id' => $otherAdmin->id,
        ]);
    }

    public function test_super_admin_can_demote_another_super_admin_when_an_active_super_admin_remains(): void
    {
        $actingSuperAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $otherSuperAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($actingSuperAdmin, 'admin')
            ->put(route('admin.admins.update', $otherSuperAdmin), [
                'name' => $otherSuperAdmin->name,
                'email' => $otherSuperAdmin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => AdminRole::Admin->value,
                'is_active' => true,
            ]);

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $otherSuperAdmin->refresh();

        $this->assertSame(
            AdminRole::Admin,
            $otherSuperAdmin->role,
        );
    }

    public function test_super_admin_can_deactivate_another_super_admin_when_an_active_super_admin_remains(): void
    {
        $actingSuperAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $otherSuperAdmin = Admin::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($actingSuperAdmin, 'admin')
            ->put(route('admin.admins.update', $otherSuperAdmin), [
                'name' => $otherSuperAdmin->name,
                'email' => $otherSuperAdmin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => AdminRole::SuperAdmin->value,
                'is_active' => false,
            ]);

        $response
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHas('success');

        $this->assertFalse(
            $otherSuperAdmin->fresh()->is_active,
        );
    }

    public function test_inactive_admin_is_logged_out_when_accessing_protected_admin_routes(): void
    {
        $admin = Admin::factory()->superAdmin()->create([
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'));

        $response
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}
