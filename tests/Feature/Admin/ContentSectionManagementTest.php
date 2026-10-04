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

final class ContentSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_all_content_templates(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $templates = [
            'text' => [
                'heading' => 'About Our Store',
                'body' => 'We make shopping simple.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],
            'image_text' => [
                'heading' => 'Built for Everyday Shopping',
                'body' => 'Discover products selected for quality and value.',
                'image' => '/storage/page-builder/content/image-text.webp',
                'image_alt' => 'A selection of products in our store',
                'alignment' => 'left',
            ],
            'text_image' => [
                'heading' => 'Our Promise',
                'body' => 'Clear information, dependable service, and useful products.',
                'image' => '/storage/page-builder/content/text-image.webp',
                'image_alt' => 'Customer receiving an order',
                'alignment' => 'right',
            ],
            'centered_content' => [
                'heading' => 'Why Shop With Us',
                'body' => 'A simple shopping experience from discovery to delivery.',
                'image' => '/storage/page-builder/content/centered.webp',
                'image_alt' => 'Our storefront',
                'alignment' => 'center',
            ],
        ];

        foreach ($templates as $template => $config) {
            $response = $this
                ->actingAs($admin, 'admin')
                ->post(
                    route(
                        'admin.pages.sections.store',
                        $page,
                    ),
                    [
                        'type' => SectionType::Content->value,
                        'template' => $template,
                        'config' => $config,
                        'is_enabled' => true,
                    ],
                );

            $response
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $sections = PageSection::query()
            ->where('page_id', $page->id)
            ->where(
                'type',
                SectionType::Content->value,
            )
            ->orderBy('position')
            ->get();

        $this->assertCount(
            4,
            $sections,
        );

        $this->assertSame(
            [
                'text',
                'image_text',
                'text_image',
                'centered_content',
            ],
            $sections
                ->pluck('template')
                ->all(),
        );

        $this->assertSame(
            [
                10,
                20,
                30,
                40,
            ],
            $sections
                ->pluck('position')
                ->all(),
        );

        foreach ($sections as $section) {
            $this->assertSame(
                $templates[$section->template],
                $section->config,
            );

            $this->assertTrue(
                $section->is_enabled,
            );
        }
    }

    public function test_editor_can_change_content_template_and_update_normalized_config(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()
            ->for($page)
            ->create([
                'type' => SectionType::Content,
                'template' => 'text',
                'config' => [
                    'heading' => 'About Us',
                    'body' => 'Original body.',
                    'image' => null,
                    'image_alt' => null,
                    'alignment' => 'left',
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
                    'type' => SectionType::Content->value,
                    'template' => 'image_text',
                    'config' => [
                        'heading' => '  Updated Content  ',
                        'body' => '  Updated body content.  ',
                        'image' => '  /storage/page-builder/content/updated.webp  ',
                        'image_alt' => '  Updated content image  ',
                        'alignment' => 'right',
                    ],
                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $section->refresh();

        $this->assertSame(
            SectionType::Content,
            $section->type,
        );

        $this->assertSame(
            'image_text',
            $section->template,
        );

        $this->assertSame(
            [
                'heading' => 'Updated Content',
                'body' => 'Updated body content.',
                'image' => '/storage/page-builder/content/updated.webp',
                'image_alt' => 'Updated content image',
                'alignment' => 'right',
            ],
            $section->config,
        );
    }

    public function test_content_section_with_empty_body_cannot_be_updated(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $originalConfig = [
            'heading' => 'About Us',
            'body' => 'Original body.',
            'image' => null,
            'image_alt' => null,
            'alignment' => 'left',
        ];

        $section = PageSection::factory()
            ->for($page)
            ->create([
                'type' => SectionType::Content,
                'template' => 'text',
                'config' => $originalConfig,
                'is_enabled' => true,
            ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->from(
                route(
                    'admin.pages.builder',
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
                    'type' => SectionType::Content->value,
                    'template' => 'text',
                    'config' => [
                        'heading' => 'Still invalid',
                        'body' => '',
                        'image' => null,
                        'image_alt' => null,
                        'alignment' => 'left',
                    ],
                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->assertSessionHasErrors();

        $section->refresh();

        $this->assertSame(
            $originalConfig,
            $section->config,
        );
    }

    public function test_content_http_boundary_rejects_unknown_configuration_fields(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $response = $this
            ->actingAs($admin, 'admin')
            ->from(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->post(
                route(
                    'admin.pages.sections.store',
                    $page,
                ),
                [
                    'type' => SectionType::Content->value,
                    'template' => 'text',
                    'config' => [
                        'heading' => 'Unsafe Content',
                        'body' => 'This body itself is plain text.',
                        'image' => null,
                        'image_alt' => null,
                        'alignment' => 'left',
                        'unsafe_html' => '<script>alert(1)</script>',
                    ],
                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }
}
