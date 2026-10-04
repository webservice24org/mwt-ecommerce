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

final class PromotionalBannerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_all_promotional_banner_templates(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $templates = [
            'image_banner' => [
                'heading' => 'Image Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'center',
            ],
            'content_banner' => [
                'heading' => 'Content Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'center',
            ],
            'split_banner' => [
                'heading' => 'Split Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'left',
            ],
        ];

        foreach ($templates as $template => $config) {
            $response = $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->post(
                    route(
                        'admin.pages.sections.store',
                        $page,
                    ),
                    [
                        'type' => SectionType::PromotionalBanner->value,
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
            ->where(
                'page_id',
                $page->id,
            )
            ->where(
                'type',
                SectionType::PromotionalBanner->value,
            )
            ->orderBy('position')
            ->get();

        $this->assertCount(
            3,
            $sections,
        );

        $this->assertSame(
            [
                'image_banner',
                'content_banner',
                'split_banner',
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
            ],
            $sections
                ->pluck('position')
                ->all(),
        );

        foreach ($sections as $section) {
            $expected = $templates[
                $section->template
            ];

            $this->assertSame(
                $expected,
                $section->config,
            );

            $this->assertTrue(
                $section->is_enabled,
            );
        }
    }

    public function test_empty_description_survives_http_middleware_and_is_normalized(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.pages.sections.store',
                    $page,
                ),
                [
                    'type' => SectionType::PromotionalBanner->value,
                    'template' => 'image_banner',
                    'config' => [
                        'heading' => 'Special Offer',
                        'description' => '',
                        'image' => null,
                        'cta_label' => null,
                        'cta_url' => null,
                        'alignment' => 'center',
                    ],
                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $section = PageSection::query()
            ->where(
                'page_id',
                $page->id,
            )
            ->where(
                'type',
                SectionType::PromotionalBanner->value,
            )
            ->firstOrFail();

        $this->assertSame(
            '',
            $section->config['description'],
        );
    }

    public function test_editor_can_update_promotional_banner_media_cta_and_alignment(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $section = PageSection::factory()
            ->for($page)
            ->create([
                'type' => SectionType::PromotionalBanner,
                'template' => 'image_banner',
                'config' => [
                    'heading' => 'Special Offer',
                    'description' => '',
                    'image' => null,
                    'cta_label' => null,
                    'cta_url' => null,
                    'alignment' => 'center',
                ],
                'is_enabled' => true,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
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
                    'type' => SectionType::PromotionalBanner->value,
                    'template' => 'image_banner',
                    'config' => [
                        'heading' => '  Weekend Sale  ',
                        'description' => '  Save on selected products.  ',
                        'image' => '  /storage/page-builder/banner.webp  ',
                        'cta_label' => '  Shop Now  ',
                        'cta_url' => '  /products  ',
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
            [
                'heading' => 'Weekend Sale',
                'description' => 'Save on selected products.',
                'image' => '/storage/page-builder/banner.webp',
                'cta_label' => 'Shop Now',
                'cta_url' => '/products',
                'alignment' => 'right',
            ],
            $section->config,
        );
    }

    public function test_promotional_banner_rejects_incomplete_cta_pair(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
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
                    'type' => SectionType::PromotionalBanner->value,
                    'template' => 'content_banner',
                    'config' => [
                        'heading' => 'Special Offer',
                        'description' => '',
                        'image' => null,
                        'cta_label' => 'Shop Now',
                        'cta_url' => null,
                        'alignment' => 'center',
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
