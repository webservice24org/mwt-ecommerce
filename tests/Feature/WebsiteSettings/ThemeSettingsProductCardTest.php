<?php

declare(strict_types=1);

namespace Tests\Feature\WebsiteSettings;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ThemeSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ThemeSettingsProductCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_read_only_and_admin_can_publish_and_reset_styles(): void
    {
        $admin = $this->admin(AdminRole::Admin);
        $edit = route('admin.website-settings.theme-settings.edit');
        $update = route('admin.website-settings.theme-settings.product-cards.update');
        $this->get(route('home'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('storefrontTheme.product_cards', []),
        );
        $this->assertDatabaseCount('theme_settings', 0);

        $this->actingAs($admin, 'admin')->get($edit)->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Admin/WebsiteSettings/ThemeSettings/Edit')
                ->where('productCards', [])->where('abilities.update', true)->where('storefrontTheme', null),
        );
        $config = [
            'background_color' => '#ffffff',
            'title_color' => '#123456',
            'regular_price_color' => '#111111',
            'offer_price_color' => '#b91c1c',
            'add_background_color' => '#eeeeee',
            'order_border_radius' => 0,
            'order_hover_text_color' => '#ffffff',
        ];
        $this->put($update, ['product_cards' => $config])->assertRedirect($edit)->assertSessionHas('success');
        $this->assertDatabaseCount('theme_settings', 1);
        self::assertEquals($config, ThemeSetting::productCardStyles());
        $this->get($edit)->assertOk()->assertInertia(fn (Assert $page) => $page->where('productCards', $config));
        $product = Product::factory()->published()->create();
        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('storefrontTheme.product_cards', $config),
        );
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('storefrontTheme.product_cards', $config));
        $this->put($update, ['product_cards' => []])->assertRedirect($edit);
        self::assertSame([], ThemeSetting::productCardStyles());
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('storefrontTheme.product_cards', []));
    }

    public function test_title_hover_styles_publish_to_storefront_and_reset_without_losing_other_styles(): void
    {
        $edit = route('admin.website-settings.theme-settings.edit');
        $update = route('admin.website-settings.theme-settings.product-cards.update');
        $this->actingAs($this->admin(AdminRole::Admin), 'admin');
        $config = [
            'title_color' => '#123456',
            'title_hover_color' => '#dc2626',
            'title_hover_decoration' => 'none',
            'order_border_radius' => 8,
        ];
        $this->put($update, ['product_cards' => $config])->assertRedirect($edit);
        $this->get($edit)->assertOk()->assertInertia(fn (Assert $page) => $page->where('productCards', $config));
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('storefrontTheme.product_cards', $config));
        $this->putJson($update, ['product_cards' => ['title_hover_decoration' => 'none; color:red']])
            ->assertUnprocessable()->assertJsonValidationErrors('product_cards.title_hover_decoration');
        self::assertSame($config, ThemeSetting::productCardStyles());

        unset($config['title_hover_color'], $config['title_hover_decoration']);
        $this->put($update, ['product_cards' => $config])->assertRedirect($edit);
        self::assertSame($config, ThemeSetting::productCardStyles());
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('storefrontTheme.product_cards', $config));
    }

    public function test_permissions_match_existing_builder_policy(): void
    {
        $edit = route('admin.website-settings.theme-settings.edit');
        $update = route('admin.website-settings.theme-settings.product-cards.update');
        $this->get($edit)->assertRedirect(route('admin.login'));
        $this->putJson($update, ['product_cards' => []])->assertUnauthorized();
        $this->actingAs($this->admin(AdminRole::Editor), 'admin')->get($edit)->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('abilities.update', false),
        );
        $this->put($update, ['product_cards' => []])->assertForbidden();
        $this->actingAs($this->admin(AdminRole::Manager), 'admin')->put($update, ['product_cards' => ['title_color' => '#abcdef']])->assertRedirect($edit);
        $this->actingAs($this->admin(AdminRole::SuperAdmin), 'admin')->put($update, ['product_cards' => []])->assertRedirect($edit);
    }

    public function test_invalid_requests_do_not_overwrite_settings_or_other_sections(): void
    {
        ThemeSetting::query()->create(['section' => 'future_section', 'config' => ['private' => 'keep']]);
        $update = route('admin.website-settings.theme-settings.product-cards.update');
        $this->actingAs($this->admin(AdminRole::Admin), 'admin')->put($update, ['product_cards' => ['title_color' => '#abcdef']])->assertRedirect();
        $this->putJson($update, ['product_cards' => ['title_color' => 'red', 'custom_css' => 'body{}', 'order_border_radius' => -1]])
            ->assertUnprocessable()->assertJsonValidationErrors(['product_cards.title_color', 'product_cards.custom_css', 'product_cards.order_border_radius']);
        $this->putJson($update, [])->assertUnprocessable()->assertJsonValidationErrors('product_cards');
        self::assertSame(['title_color' => '#abcdef'], ThemeSetting::productCardStyles());
        $this->assertDatabaseCount('theme_settings', 2);
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('storefrontTheme', 1)->missing('storefrontTheme.future_section'));
    }

    public function test_public_read_whitelists_saved_values_even_if_database_contains_bad_data(): void
    {
        ThemeSetting::query()->create(['section' => ThemeSetting::PRODUCT_CARDS, 'config' => [
            'title_color' => '#123456', 'background_color' => 'url(https://example.com)', 'secret' => 'hidden',
        ]]);
        $this->get(route('home'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('storefrontTheme.product_cards', ['title_color' => '#123456']),
        );
    }

    private function admin(AdminRole $role): Admin
    {
        return Admin::factory()->create(['role' => $role, 'is_active' => true]);
    }
}
