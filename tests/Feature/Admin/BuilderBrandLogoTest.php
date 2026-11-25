<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\Admin;
use App\Models\FooterSetting;
use App\Models\HeaderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BuilderBrandLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_uploads_use_separate_public_directories_without_publishing(): void
    {
        Storage::fake('public');
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]), 'admin');

        foreach (['header', 'footer'] as $section) {
            $response = $this->postJson(route('admin.website-settings.'.$section.'-builder.logo.store'), [
                'image' => UploadedFile::fake()->image('logo.png', 300, 100),
            ])->assertOk();
            $path = $response->json('image.path');
            self::assertIsString($path);
            self::assertStringStartsWith('branding/'.$section.'/', $path);
            $response->assertJsonPath('image.url', '/storage/'.$path);
            Storage::disk('public')->assertExists($path);
        }

        self::assertSame(0, HeaderSetting::query()->count());
        self::assertSame(0, FooterSetting::query()->count());
    }

    public function test_read_only_admin_cannot_upload_logos(): void
    {
        Storage::fake('public');
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Editor, 'is_active' => true]), 'admin');
        foreach (['header', 'footer'] as $section) {
            $this->postJson(route('admin.website-settings.'.$section.'-builder.logo.store'), [
                'image' => UploadedFile::fake()->image('logo.png'),
            ])->assertForbidden();
        }
        self::assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]), 'admin');
        foreach (['header', 'footer'] as $section) {
            $this->postJson(route('admin.website-settings.'.$section.'-builder.logo.store'), [
                'image' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
            ])->assertUnprocessable()->assertJsonValidationErrors('image');
        }
        self::assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_header_mode_is_saved_and_unsafe_url_is_rejected(): void
    {
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]), 'admin');
        $route = route('admin.website-settings.header-builder.update');
        $payload = ['template' => 'mega_menu', 'is_enabled' => true, 'config' => ['brand' => [
            'name' => 'Store', 'display_mode' => 'logo', 'logo_url' => '/storage/branding/header/logo.png',
        ]]];
        $this->put($route, $payload)->assertRedirect();
        self::assertSame('logo', HeaderSetting::singleton()->config['brand']['display_mode']);
        $payload['config']['brand']['logo_url'] = 'javascript:alert(1)';
        $this->putJson($route, $payload)->assertUnprocessable()->assertJsonValidationErrors('brand.logo_url');
        $payload['config']['brand']['display_mode'] = 'text';
        $payload['config']['brand']['logo_url'] = null;
        $this->put($route, $payload)->assertRedirect();
        self::assertSame('text', HeaderSetting::singleton()->config['brand']['display_mode']);
    }

    public function test_footer_logo_is_preserved_for_every_template(): void
    {
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]), 'admin');
        foreach (FooterTemplate::cases() as $template) {
            $config = (new FooterConfigDefaults)->for($template);
            $config['brand'] = ['name' => 'Store', 'description' => 'Description', 'display_mode' => 'logo', 'logo_url' => '/storage/branding/footer/logo.png'];
            $this->put(route('admin.website-settings.footer-builder.update'), [
                'template' => $template->value, 'is_enabled' => true, 'config' => $config,
            ])->assertRedirect();
            foreach ($config['brand'] as $key => $value) {
                self::assertSame($value, FooterSetting::singleton()->config['brand'][$key]);
            }
        }
    }
}
