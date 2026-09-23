<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'content_mode' => PageContentMode::Classic->value,
                'content' => '<p>About our company.</p>',
            ],
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
                    'title' => '',
                    'status' => 'invalid-status',
                    'content_mode' => 'invalid-content-mode',
                ],
            )
            ->assertSessionHasErrors([
                'type',
                'title',
                'status',
                'content_mode',
            ]);

        $this->assertDatabaseCount(
            'pages',
            0,
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
}
