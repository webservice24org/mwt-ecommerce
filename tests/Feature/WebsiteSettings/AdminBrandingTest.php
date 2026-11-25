<?php

declare(strict_types=1);

namespace Tests\Feature\WebsiteSettings;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\WebsiteSettings\AdminBrandingSchema;
use App\Models\Admin;
use App\Models\ThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_defaults_and_public_storefront_boundary(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Admin/Auth/Login')->where('adminBranding', []),
        );
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('adminBranding', null));
        $this->assertDatabaseCount('theme_settings', 0);
    }

    public function test_upload_save_and_login_branding_are_independent_of_product_card_settings(): void
    {
        Storage::fake('public');
        ThemeSetting::query()->create(['section' => ThemeSetting::PRODUCT_CARDS, 'config' => ['title_color' => '#123456']]);
        $this->actingAs($this->admin(AdminRole::Admin), 'admin');
        $config = ['background_mode' => 'image', 'background_color' => '#123456'];
        foreach (AdminBrandingSchema::IMAGE_FIELDS as $kind) {
            $response = $this->postJson(route('admin.website-settings.theme-settings.admin-branding.upload', ['kind' => $kind]), [
                'image' => UploadedFile::fake()->image('brand.png', 200, 100),
            ])->assertOk();
            $url = $response->json('image.url');
            self::assertIsString($url);
            self::assertStringStartsWith('/storage/branding/admin/'.$kind.'/', $url);
            Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
            $config[$kind] = $url;
        }
        self::assertSame([], AdminBrandingSchema::saved());
        $update = route('admin.website-settings.theme-settings.admin-branding.update');
        $edit = route('admin.website-settings.theme-settings.edit');
        $this->put($update, ['admin_branding' => $config])->assertRedirect($edit)->assertSessionHas('success');
        $this->get($edit)->assertOk()->assertInertia(fn (Assert $page) => $page->where('adminBranding', $config)->where('productCards', ['title_color' => '#123456']));
        $this->get(route('admin.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('adminBranding', $config));
        $this->post(route('admin.logout'))->assertRedirect();
        $this->get(route('admin.login'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('adminBranding', $config));
    }

    public function test_color_and_reset_leave_other_sections_and_files_unchanged(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(AdminRole::Manager), 'admin');
        $update = route('admin.website-settings.theme-settings.admin-branding.update');
        ThemeSetting::query()->create(['section' => 'future_section', 'config' => ['keep' => 'value']]);
        $this->put($update, ['admin_branding' => ['background_mode' => 'color', 'background_color' => '#ABCDEF']])->assertRedirect();
        self::assertEquals(['background_mode' => 'color', 'background_color' => '#abcdef'], AdminBrandingSchema::saved());
        $this->put($update, ['admin_branding' => []])->assertRedirect();
        self::assertSame([], AdminBrandingSchema::saved());
        $this->assertDatabaseCount('theme_settings', 2);
    }

    public function test_editor_and_guest_cannot_mutate_admin_branding(): void
    {
        Storage::fake('public');
        $update = route('admin.website-settings.theme-settings.admin-branding.update');
        $upload = route('admin.website-settings.theme-settings.admin-branding.upload', ['kind' => 'login_logo']);
        $this->putJson($update, ['admin_branding' => []])->assertUnauthorized();
        $this->postJson($upload, ['image' => UploadedFile::fake()->image('logo.png')])->assertUnauthorized();
        $this->actingAs($this->admin(AdminRole::Editor), 'admin');
        $this->get(route('admin.website-settings.theme-settings.edit'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('abilities.update', false));
        $this->putJson($update, ['admin_branding' => []])->assertForbidden();
        $this->postJson($upload, ['image' => UploadedFile::fake()->image('logo.png')])->assertForbidden();
        self::assertSame([], Storage::disk('public')->allFiles('branding/admin'));
    }

    public function test_invalid_css_external_paths_and_unsafe_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(AdminRole::SuperAdmin), 'admin');
        $update = route('admin.website-settings.theme-settings.admin-branding.update');
        $this->putJson($update, ['admin_branding' => ['background_mode' => 'image', 'background_color' => 'url(evil)', 'background_image' => 'https://example.com/a.png', 'login_logo' => '/storage/branding/admin/sidebar_logo/'.str_repeat('a', 40).'.png', 'custom_css' => 'body{}']])
            ->assertUnprocessable()->assertJsonValidationErrors(['admin_branding.background_color', 'admin_branding.background_image', 'admin_branding.login_logo', 'admin_branding.custom_css']);
        $this->putJson($update, ['admin_branding' => ['login_logo' => '/storage/branding/admin/login_logo/'.str_repeat('a', 40).'.png']])->assertUnprocessable()->assertJsonValidationErrors('admin_branding.login_logo');
        $this->postJson(route('admin.website-settings.theme-settings.admin-branding.upload', ['kind' => 'login_logo']), ['image' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml')])->assertUnprocessable()->assertJsonValidationErrors('image');
        self::assertSame([], AdminBrandingSchema::saved());
    }

    public function test_invalid_database_values_are_filtered_on_guest_login(): void
    {
        ThemeSetting::query()->create(['section' => AdminBrandingSchema::SECTION, 'config' => ['background_mode' => 'image', 'background_image' => 'javascript:alert(1)', 'secret' => 'private']]);
        $this->get(route('admin.login'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('adminBranding', []));
    }

    private function admin(AdminRole $role): Admin
    {
        return Admin::factory()->create(['role' => $role, 'is_active' => true]);
    }
}
