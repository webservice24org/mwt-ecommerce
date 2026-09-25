<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Admin;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_add_section_to_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $response = $this
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
                        'limit' => 8,
                    ],
                    'is_enabled' => true,
                ],
            );

        $response->assertRedirect();

        $this->assertDatabaseHas(
            'page_sections',
            [
                'page_id' => $page->id,
                'type' => SectionType::FeaturedProducts->value,
                'template' => 'grid',
                'position' => 10,
                'is_enabled' => true,
            ],
        );
    }

    public function test_invalid_section_type_is_rejected(): void
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
                    'type' => 'arbitrary_php_component',
                    'template' => 'grid',
                    'config' => [],
                ],
            )
            ->assertSessionHasErrors([
                'type',
            ]);

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }

    public function test_editor_can_delete_section_from_its_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.pages.sections.destroy',
                    [
                        'page' => $page,
                        'section' => $section->id,
                    ],
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'page_sections',
            [
                'id' => $section->id,
            ],
        );
    }

    public function test_section_from_another_page_cannot_be_deleted(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $otherPage = Page::factory()->create();

        $otherSection = PageSection::factory()->create([
            'page_id' => $otherPage->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.pages.sections.destroy',
                    [
                        'page' => $page,
                        'section' => $otherSection->id,
                    ],
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'page_sections',
            [
                'id' => $otherSection->id,
                'page_id' => $otherPage->id,
            ],
        );
    }

    public function test_editor_can_reorder_page_sections(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $first = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
        ]);

        $second = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 20,
        ]);

        $third = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 30,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.reorder',
                    $page,
                ),
                [
                    'section_ids' => [
                        $third->id,
                        $first->id,
                        $second->id,
                    ],
                ],
            )
            ->assertRedirect();

        $this->assertSame(
            10,
            $third->refresh()->position,
        );

        $this->assertSame(
            20,
            $first->refresh()->position,
        );

        $this->assertSame(
            30,
            $second->refresh()->position,
        );
    }

    public function test_reorder_rejects_duplicate_section_ids(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $first = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.reorder',
                    $page,
                ),
                [
                    'section_ids' => [
                        $first->id,
                        $first->id,
                    ],
                ],
            )
            ->assertSessionHasErrors([
                'section_ids.1',
            ]);
    }

    public function test_editor_cannot_reorder_using_section_from_another_page(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
        ]);

        $otherPage = Page::factory()->create();

        $otherSection = PageSection::factory()->create([
            'page_id' => $otherPage->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.reorder',
                    $page,
                ),
                [
                    'section_ids' => [
                        $section->id,
                        $otherSection->id,
                    ],
                ],
            )
            ->assertSessionHasErrors([
                'section_ids',
            ]);

        $this->assertSame(
            10,
            $section->refresh()->position,
        );

        $this->assertDatabaseHas(
            'page_sections',
            [
                'id' => $otherSection->id,
                'page_id' => $otherPage->id,
            ],
        );
    }

    public function test_unauthenticated_user_cannot_add_section(): void
    {
        $page = Page::factory()->create();

        $this
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
                        'limit' => 8,
                    ],
                    'is_enabled' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }

    public function test_authorized_admin_can_update_a_page_section(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()
            ->for($page)
            ->create([
                'type' => SectionType::FeaturedProducts,
                'template' => 'grid',
                'config' => [
                    'title' => 'Featured Products',
                    'limit' => 8,
                ],
                'is_enabled' => true,
            ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,
                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::FeaturedProducts->value,
                    'template' => 'grid',
                    'config' => [
                        'title' => 'Latest Products',
                        'limit' => 12,
                    ],
                    'is_enabled' => false,
                ],
            );

        $response->assertRedirect();

        $section->refresh();

        $this->assertSame(
            SectionType::FeaturedProducts,
            $section->type,
        );

        $this->assertSame(
            'grid',
            $section->template,
        );

        $this->assertSame(
            [
                'title' => 'Latest Products',
                'limit' => 12,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $section->config,
        );

        $this->assertFalse(
            $section->is_enabled,
        );
    }

    public function test_invalid_page_section_configuration_cannot_be_updated(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()
            ->for($page)
            ->create([
                'type' => SectionType::FeaturedProducts,
                'template' => 'grid',
                'config' => [
                    'title' => 'Featured Products',
                    'limit' => 8,
                ],
            ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->from(
                route(
                    'admin.pages.edit',
                    $page,
                ),
            )
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,
                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::FeaturedProducts->value,
                    'template' => 'grid',
                    'config' => [
                        'title' => 'Featured Products',
                        'limit' => 999,
                    ],
                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.pages.edit',
                    $page,
                ),
            )
            ->assertSessionHasErrors();
    }

    public function test_page_section_from_another_page_cannot_be_updated(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();
        $otherPage = Page::factory()->create();

        $section = PageSection::factory()
            ->for($otherPage)
            ->create();

        $response = $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,
                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::FeaturedProducts->value,
                    'template' => 'grid',
                    'config' => [
                        'title' => 'Featured Products',
                        'limit' => 8,
                    ],
                    'is_enabled' => true,
                ],
            );

        $response->assertNotFound();
    }

    public function test_unknown_section_type_cannot_be_updated(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()
            ->for($page)
            ->create();

        $response = $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,
                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => 'unknown_section',
                    'template' => 'grid',
                    'config' => [],
                    'is_enabled' => true,
                ],
            );

        $response->assertSessionHasErrors(
            'type',
        );
    }
}
