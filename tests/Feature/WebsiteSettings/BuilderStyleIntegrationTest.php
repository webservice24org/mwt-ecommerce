<?php

declare(strict_types=1);

namespace Tests\Feature\WebsiteSettings;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\FooterSetting;
use App\Models\HeaderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BuilderStyleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_styles_and_storefront_receives_them(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]);
        $headerStyle = ['main' => ['text_color' => '#123456', 'background_color' => '#ffffff', 'text_size' => 18]];
        $footerStyle = ['bottom' => ['text_color' => '#eeeeee', 'background_color' => '#112233', 'text_size' => 12]];

        $this->actingAs($admin, 'admin')->put(route('admin.website-settings.header-builder.update'), [
            'template' => 'mega_menu',
            'is_enabled' => true,
            'config' => ['style' => $headerStyle],
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin, 'admin')->put(route('admin.website-settings.footer-builder.update'), [
            'template' => 'luxe_newsletter',
            'is_enabled' => true,
            'config' => ['style' => $footerStyle],
        ])->assertRedirect()->assertSessionHas('success');

        $this->get(route('home'))->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->where('storefrontHeader.config.style', $headerStyle)
                ->where('storefrontFooter.config.style', $footerStyle),
        );

        $this->actingAs($admin, 'admin')->get(route('admin.website-settings.header-builder.edit'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('header.config.style', $headerStyle));
        $this->actingAs($admin, 'admin')->get(route('admin.website-settings.footer-builder.edit'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('footer.config.style', $footerStyle));

        $header = HeaderSetting::singleton();
        $footer = FooterSetting::singleton();
        $headerConfig = $header->config;
        $footerConfig = $footer->getAttribute('config');
        self::assertIsArray($footerConfig);
        unset($headerConfig['style'], $footerConfig['style']);

        $this->actingAs($admin, 'admin')->put(route('admin.website-settings.header-builder.update'), [
            'template' => 'mega_menu', 'is_enabled' => true, 'config' => $headerConfig,
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($admin, 'admin')->put(route('admin.website-settings.footer-builder.update'), [
            'template' => 'luxe_newsletter', 'is_enabled' => true, 'config' => $footerConfig,
        ])->assertRedirect()->assertSessionHas('success');

        $this->get(route('home'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->missing('storefrontHeader.config.style')->missing('storefrontFooter.config.style'),
        );
    }

    public function test_invalid_style_is_rejected_without_overwriting_saved_config(): void
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Admin, 'is_active' => true]);
        $header = HeaderSetting::singleton();
        $footer = FooterSetting::singleton();
        $originalHeader = $header->config;
        $originalFooter = $footer->getAttribute('config');

        foreach (['header-builder' => 'mega_menu', 'footer-builder' => 'luxe_newsletter'] as $builder => $template) {
            $this->actingAs($admin, 'admin')->from(route('admin.website-settings.'.$builder.'.edit'))
                ->put(route('admin.website-settings.'.$builder.'.update'), [
                    'template' => $template,
                    'is_enabled' => true,
                    'config' => ['style' => ['main' => ['background_color' => 'url(https://example.com)', 'text_size' => 999]]],
                ])->assertRedirect()->assertSessionHasErrors(['style.main.background_color', 'style.main.text_size']);
        }

        self::assertSame($originalHeader, $header->refresh()->config);
        self::assertSame($originalFooter, $footer->refresh()->getAttribute('config'));
    }
}
