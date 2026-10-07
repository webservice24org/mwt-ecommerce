<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageLayout;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_view_page_index(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.pages.index'))
            ->assertOk();
    }

    public function test_editor_can_create_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.pages.store'),
                [
                    'type' => PageType::Standard->value,
                    'layout' => PageLayout::LeftSidebar->value,
                    'show_breadcrumbs' => false,
                    'title' => 'About Us',
                    'slug' => '',
                    'status' => PageStatus::Draft->value,
                    'content_mode' => PageContentMode::Classic->value,
                    'content' => '<p>About our company.</p>',
                    'meta_title' => '',
                    'meta_description' => '',
                    'published_at' => '',
                ],
            );

        $page = Page::query()
            ->where(
                'title',
                'About Us',
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'admin.pages.edit',
                $page,
            ),
        );

        $this->assertSame(
            'about-us',
            $page->slug,
        );

        $this->assertSame(
            PageContentMode::Classic,
            $page->content_mode,
        );

        $this->assertSame(
            '<p>About our company.</p>',
            $page->content,
        );

        $this->assertSame(
            PageLayout::LeftSidebar,
            $page->layout,
        );

        $this->assertFalse(
            $page->show_breadcrumbs,
        );

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'layout' => PageLayout::LeftSidebar->value,
                'show_breadcrumbs' => false,
                'content_mode' => PageContentMode::Classic->value,
                'content' => '<p>About our company.</p>',
            ],
        );
    }

    public function test_editor_can_view_page_edit_without_builder_registry_data(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create([
            'layout' => PageLayout::RightSidebar,
            'show_breadcrumbs' => false,
            'content_mode' => PageContentMode::Builder,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(
                route(
                    'admin.pages.edit',
                    $page,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia): Assert => $inertia
                    ->component(
                        'Admin/PageBuilder/Pages/Edit',
                    )
                    ->where(
                        'page.id',
                        $page->id,
                    )
                    ->where(
                        'page.content_mode',
                        PageContentMode::Builder->value,
                    )
                    ->where(
                        'page.layout',
                        PageLayout::RightSidebar->value,
                    )
                    ->where(
                        'page.show_breadcrumbs',
                        false,
                    )
                    ->missing(
                        'sectionDefinitions',
                    ),
            );
    }

    public function test_editor_can_update_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                [
                    'type' => PageType::Standard->value,
                    'layout' => PageLayout::RightSidebar->value,
                    'show_breadcrumbs' => false,
                    'title' => 'Updated Page',
                    'slug' => 'updated-page',
                    'status' => PageStatus::Draft->value,
                    'content_mode' => PageContentMode::Classic->value,
                    'content' => '<p>Updated page content.</p>',
                    'meta_title' => '',
                    'meta_description' => '',
                    'published_at' => '',
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'layout' => PageLayout::RightSidebar->value,
                'show_breadcrumbs' => false,
                'title' => 'Updated Page',
                'slug' => 'updated-page',
                'content_mode' => PageContentMode::Classic->value,
                'content' => '<p>Updated page content.</p>',
            ],
        );
    }

    public function test_editor_cannot_delete_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.pages.destroy',
                    $page,
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
            ],
        );
    }

    public function test_manager_can_delete_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $page = Page::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.pages.destroy',
                    $page,
                ),
            )
            ->assertRedirect(
                route('admin.pages.index'),
            );

        $this->assertDatabaseMissing(
            'pages',
            [
                'id' => $page->id,
            ],
        );
    }

    public function test_invalid_page_payload_is_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.pages.store'),
                [
                    'type' => 'invalid-type',
                    'layout' => 'invalid-layout',
                    'title' => '',
                    'status' => 'invalid-status',
                    'content_mode' => 'invalid-content-mode',
                ],
            )
            ->assertSessionHasErrors([
                'type',
                'layout',
                'title',
                'status',
                'content_mode',
            ]);

        $this->assertDatabaseCount(
            'pages',
            0,
        );
    }

    public function test_invalid_breadcrumb_visibility_is_rejected(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.pages.store'),
                [
                    'type' => PageType::Standard->value,
                    'layout' => PageLayout::FullWidth->value,
                    'show_breadcrumbs' => 'not-a-boolean',
                    'title' => 'Invalid Breadcrumb Page',
                    'status' => PageStatus::Draft->value,
                    'content_mode' => PageContentMode::Classic->value,
                ],
            )
            ->assertSessionHasErrors([
                'show_breadcrumbs',
            ]);

        $this->assertDatabaseMissing(
            'pages',
            [
                'title' => 'Invalid Breadcrumb Page',
            ],
        );
    }

    public function test_invalid_section_configuration_cannot_be_persisted(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.pages.sections.store',
                    $page,
                ),
                [
                    'type' => SectionType::FeaturedProducts->value,
                    'template' => 'grid',
                    'config' => [
                        'title' => 'Featured Products',
                        'limit' => 999,
                    ],
                ],
            )
            ->assertSessionHasErrors([
                'limit',
            ]);

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }

    public function test_editor_can_open_the_dedicated_page_builder(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create([
            'content_mode' => PageContentMode::Builder,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia): Assert => $inertia
                    ->component(
                        'Admin/PageBuilder/Pages/Builder',
                    )
                    ->where(
                        'page.id',
                        $page->id,
                    )
                    ->where(
                        'page.title',
                        $page->title,
                    )
                    ->has(
                        'sectionDefinitions',
                        10,
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Hero
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.0.type',
                        SectionType::Hero->value,
                    )
                    ->where(
                        'sectionDefinitions.0.label',
                        'Hero',
                    )
                    ->where(
                        'sectionDefinitions.0.default_template',
                        'content_slider',
                    )
                    ->has(
                        'sectionDefinitions.0.templates',
                        3,
                    )
                    ->has(
                        'sectionDefinitions.0.template_default_configs',
                        3,
                    )
                    ->has(
                        'sectionDefinitions.0.template_default_configs.content_slider',
                    )
                    ->has(
                        'sectionDefinitions.0.template_default_configs.image_slider',
                    )
                    ->has(
                        'sectionDefinitions.0.template_default_configs.static',
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.content_slider.autoplay',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.content_slider.effect',
                        'slide_left',
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.content_slider.show_arrows',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.content_slider.show_dots',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.image_slider.autoplay',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.image_slider.effect',
                        'slide_left',
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.image_slider.show_arrows',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.image_slider.show_dots',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.static.autoplay',
                        false,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.static.effect',
                        'fade',
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.static.show_arrows',
                        false,
                    )
                    ->where(
                        'sectionDefinitions.0.template_default_configs.static.show_dots',
                        false,
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Featured Products
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.1.type',
                        SectionType::FeaturedProducts->value,
                    )
                    ->where(
                        'sectionDefinitions.1.label',
                        'Featured Products',
                    )
                    ->where(
                        'sectionDefinitions.1.default_template',
                        'grid',
                    )
                    ->has(
                        'sectionDefinitions.1.template_default_configs',
                        1,
                    )
                    ->has(
                        'sectionDefinitions.1.template_default_configs.grid',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Product Categories
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.2.type',
                        SectionType::ProductCategories->value,
                    )
                    ->where(
                        'sectionDefinitions.2.label',
                        'Product Categories',
                    )
                    ->where(
                        'sectionDefinitions.2.default_template',
                        'grid',
                    )
                    ->has(
                        'sectionDefinitions.2.templates',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.2.templates.0.key',
                        'grid',
                    )
                    ->where(
                        'sectionDefinitions.2.templates.1.key',
                        'cards',
                    )
                    ->where(
                        'sectionDefinitions.2.templates.2.key',
                        'carousel',
                    )
                    ->where(
                        'sectionDefinitions.2.default_config.title',
                        'Shop by Category',
                    )
                    ->where(
                        'sectionDefinitions.2.default_config.category_ids',
                        [],
                    )
                    ->where(
                        'sectionDefinitions.2.default_config.show_name',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.2.default_config.columns',
                        4,
                    )
                    ->where(
                        'sectionDefinitions.2.default_config.show_product_count',
                        false,
                    )
                    ->has(
                        'sectionDefinitions.2.template_default_configs',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.cards.columns',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.cards.show_product_count',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.carousel.autoplay',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.carousel.autoplay_delay',
                        5000,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.carousel.show_arrows',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.carousel.show_dots',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.2.template_default_configs.carousel.effect',
                        'fade',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Product Collection
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.3.type',
                        SectionType::ProductCollection->value,
                    )
                    ->where(
                        'sectionDefinitions.3.label',
                        'Product Collection',
                    )
                    ->where(
                        'sectionDefinitions.3.default_template',
                        'grid',
                    )
                    ->has(
                        'sectionDefinitions.3.templates',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.3.templates.0.key',
                        'grid',
                    )
                    ->where(
                        'sectionDefinitions.3.templates.1.key',
                        'cards',
                    )
                    ->where(
                        'sectionDefinitions.3.templates.2.key',
                        'carousel',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Product Collection default config
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.3.default_config.title',
                        'Products',
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.limit',
                        8,
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.source.type',
                        'latest',
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.columns',
                        4,
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.show_price',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.show_rating',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.3.default_config.show_badges',
                        true,
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Product Collection template configs
                    |--------------------------------------------------------------------------
                    */

                    ->has(
                        'sectionDefinitions.3.template_default_configs',
                        3,
                    )
                    ->has(
                        'sectionDefinitions.3.template_default_configs.grid',
                    )
                    ->has(
                        'sectionDefinitions.3.template_default_configs.cards',
                    )
                    ->has(
                        'sectionDefinitions.3.template_default_configs.carousel',
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.grid.columns',
                        4,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.cards.columns',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.columns',
                        4,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.autoplay',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.autoplay_delay',
                        5000,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.show_arrows',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.show_dots',
                        true,
                    )
                    ->where(
                        'sectionDefinitions.3.template_default_configs.carousel.effect',
                        'fade',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Promotional Banner
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.4.type',
                        SectionType::PromotionalBanner->value,
                    )
                    ->where(
                        'sectionDefinitions.4.label',
                        'Promotional Banner',
                    )
                    ->where(
                        'sectionDefinitions.4.default_template',
                        'image_banner',
                    )
                    ->has(
                        'sectionDefinitions.4.templates',
                        3,
                    )
                    ->where(
                        'sectionDefinitions.4.templates.0.key',
                        'image_banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.0.label',
                        'Image Banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.0.category',
                        'Marketing',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.1.key',
                        'content_banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.1.label',
                        'Content Banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.1.category',
                        'Marketing',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.2.key',
                        'split_banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.2.label',
                        'Split Banner',
                    )
                    ->where(
                        'sectionDefinitions.4.templates.2.category',
                        'Marketing',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Promotional Banner default config
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'sectionDefinitions.4.default_config.heading',
                        'Special Offer',
                    )
                    ->where(
                        'sectionDefinitions.4.default_config.description',
                        '',
                    )
                    ->where(
                        'sectionDefinitions.4.default_config.image',
                        null,
                    )
                    ->where(
                        'sectionDefinitions.4.default_config.cta_label',
                        null,
                    )
                    ->where(
                        'sectionDefinitions.4.default_config.cta_url',
                        null,
                    )
                    ->where(
                        'sectionDefinitions.4.default_config.alignment',
                        'center',
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Promotional Banner template defaults
                    |--------------------------------------------------------------------------
                    */

                    ->has(
                        'sectionDefinitions.4.template_default_configs',
                        3,
                    )
                    ->has(
                        'sectionDefinitions.4.template_default_configs.image_banner',
                    )
                    ->has(
                        'sectionDefinitions.4.template_default_configs.content_banner',
                    )
                    ->has(
                        'sectionDefinitions.4.template_default_configs.split_banner',
                    )
                    ->where(
                        'sectionDefinitions.4.template_default_configs.image_banner.alignment',
                        'center',
                    )
                    ->where(
                        'sectionDefinitions.4.template_default_configs.content_banner.alignment',
                        'center',
                    )
                    ->where(
                        'sectionDefinitions.4.template_default_configs.split_banner.alignment',
                        'left',
                    ),
            );
    }
}
