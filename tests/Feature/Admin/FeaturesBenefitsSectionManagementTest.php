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

final class FeaturesBenefitsSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_all_features_benefits_templates(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $templates = [
            'icon_grid' => [
                'eyebrow' => 'Why choose us',

                'heading' => 'Shopping made better',

                'description' => 'Useful benefits with every order.',

                'items' => [
                    [
                        'title' => 'Fast Delivery',

                        'description' => 'Dependable delivery for your orders.',

                        'icon' => 'truck',

                        'image' => null,

                        'image_alt' => null,

                        'link_label' => 'Shipping details',

                        'link_url' => '/shipping',
                    ],
                    [
                        'title' => 'Secure Payments',

                        'description' => 'Protected checkout for every purchase.',

                        'icon' => 'shield-check',

                        'image' => null,

                        'image_alt' => null,

                        'link_label' => null,

                        'link_url' => null,
                    ],
                ],

                'columns' => 3,

                'alignment' => 'center',

                'background_color' => '#ffffff',

                'text_theme' => 'dark',
            ],

            'image_grid' => [
                'eyebrow' => 'Our service',

                'heading' => 'Benefits you can see',

                'description' => 'A visual overview of our shopping experience.',

                'items' => [
                    [
                        'title' => 'Careful Packaging',

                        'description' => 'Orders are prepared carefully before dispatch.',

                        'icon' => null,

                        'image' => '/storage/page-builder/features/packaging.webp',

                        'image_alt' => 'A carefully packed customer order',

                        'link_label' => null,

                        'link_url' => null,
                    ],
                    [
                        'title' => 'Helpful Support',

                        'description' => 'Support is available when you need assistance.',

                        'icon' => null,

                        'image' => 'https://cdn.example.com/features/support.webp',

                        'image_alt' => 'Customer support representative',

                        'link_label' => 'Contact us',

                        'link_url' => 'https://example.com/contact',
                    ],
                ],

                'columns' => 2,

                'alignment' => 'left',

                'background_color' => '#f8fafc',

                'text_theme' => 'dark',
            ],

            'horizontal_benefits' => [
                'eyebrow' => null,

                'heading' => 'Why customers choose us',

                'description' => 'Core advantages at a glance.',

                'items' => [
                    [
                        'title' => 'Fast Delivery',

                        'description' => 'Quick delivery across supported areas.',

                        'icon' => 'truck',

                        'image' => null,

                        'image_alt' => null,

                        'link_label' => 'Learn more',

                        'link_url' => '#delivery',
                    ],
                    [
                        'title' => 'Customer Care',

                        'description' => 'Helpful support throughout your order.',

                        'icon' => 'headphones',

                        'image' => null,

                        'image_alt' => null,

                        'link_label' => null,

                        'link_url' => null,
                    ],
                ],

                'columns' => 3,

                'alignment' => 'left',

                'background_color' => '#0f172a',

                'text_theme' => 'light',
            ],
        ];

        foreach (
            $templates as $template => $config
        ) {
            $this
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
                        'type' => SectionType::FeaturesBenefits->value,

                        'template' => $template,

                        'config' => $config,

                        'is_enabled' => true,
                    ],
                )
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $sections =
            PageSection::query()
                ->where(
                    'page_id',
                    $page->id,
                )
                ->where(
                    'type',
                    SectionType::FeaturesBenefits->value,
                )
                ->orderBy(
                    'position',
                )
                ->get();

        $this->assertCount(
            3,
            $sections,
        );

        $this->assertSame(
            [
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
            ],
            $sections
                ->pluck(
                    'template',
                )
                ->all(),
        );

        $this->assertSame(
            [
                10,
                20,
                30,
            ],
            $sections
                ->pluck(
                    'position',
                )
                ->all(),
        );

        foreach (
            $sections as $section
        ) {
            $this->assertSame(
                $templates[
                    $section->template
                ],
                $section->config,
            );

            $this->assertTrue(
                $section->is_enabled,
            );
        }
    }

    public function test_editor_can_change_features_benefits_template_and_config_is_normalized(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $section =
            PageSection::factory()
                ->for(
                    $page,
                )
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'icon_grid',

                    'config' => [
                        'eyebrow' => null,

                        'heading' => 'Original Benefits',

                        'description' => 'Original description.',

                        'items' => [
                            [
                                'title' => 'Delivery',

                                'description' => 'Original delivery benefit.',

                                'icon' => 'truck',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => null,

                                'link_url' => null,
                            ],
                        ],

                        'columns' => 3,

                        'alignment' => 'left',

                        'background_color' => '#ffffff',

                        'text_theme' => 'dark',
                    ],

                    'is_enabled' => true,
                ]);

        $this
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
                    'type' => SectionType::FeaturesBenefits->value,

                    'template' => 'image_grid',

                    'config' => [
                        'eyebrow' => '  Better Shopping  ',

                        'heading' => '  Updated Benefits  ',

                        'description' => '  Updated description.  ',

                        'items' => [
                            [
                                'title' => '  Better Packaging  ',

                                'description' => '  Carefully packed orders.  ',

                                'icon' => null,

                                'image' => '  /storage/page-builder/features/packaging.webp  ',

                                'image_alt' => '  Customer order packaging  ',

                                'link_label' => '  Learn more  ',

                                'link_url' => '  /shipping  ',
                            ],
                        ],

                        'columns' => 2,

                        'alignment' => 'center',

                        'background_color' => '#F8FAFC',

                        'text_theme' => 'dark',
                    ],

                    'is_enabled' => true,
                ],
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $section->refresh();

        $this->assertSame(
            SectionType::FeaturesBenefits,
            $section->type,
        );

        $this->assertSame(
            'image_grid',
            $section->template,
        );

        $this->assertSame(
            [
                'eyebrow' => 'Better Shopping',

                'heading' => 'Updated Benefits',

                'description' => 'Updated description.',

                'items' => [
                    [
                        'title' => 'Better Packaging',

                        'description' => 'Carefully packed orders.',

                        'icon' => null,

                        'image' => '/storage/page-builder/features/packaging.webp',

                        'image_alt' => 'Customer order packaging',

                        'link_label' => 'Learn more',

                        'link_url' => '/shipping',
                    ],
                ],

                'columns' => 2,

                'alignment' => 'center',

                'background_color' => '#f8fafc',

                'text_theme' => 'dark',
            ],
            $section->config,
        );
    }

    public function test_unsafe_features_benefits_update_is_rejected_and_existing_config_is_preserved(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $originalConfig = [
            'eyebrow' => null,

            'heading' => 'Safe Benefits',

            'description' => 'Existing safe configuration.',

            'items' => [
                [
                    'title' => 'Fast Delivery',

                    'description' => 'Safe benefit.',

                    'icon' => 'truck',

                    'image' => null,

                    'image_alt' => null,

                    'link_label' => null,

                    'link_url' => null,
                ],
            ],

            'columns' => 3,

            'alignment' => 'left',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',
        ];

        $section =
            PageSection::factory()
                ->for(
                    $page,
                )
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'icon_grid',

                    'config' => $originalConfig,

                    'is_enabled' => true,
                ]);

        $this
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
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,

                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::FeaturesBenefits->value,

                    'template' => 'icon_grid',

                    'config' => [
                        'eyebrow' => null,

                        'heading' => 'Unsafe Benefits',

                        'description' => 'This must not persist.',

                        'items' => [
                            [
                                'title' => 'Unsafe Item',

                                'description' => 'Unsafe configuration.',

                                'icon' => 'arbitrary-svg',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => 'Open',

                                'link_url' => 'javascript:alert(1)',
                            ],
                        ],

                        'columns' => 3,

                        'alignment' => 'left',

                        'background_color' => '#ffffff',

                        'text_theme' => 'dark',
                    ],

                    'is_enabled' => true,
                ],
            )
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

        $this->assertSame(
            'icon_grid',
            $section->template,
        );
    }

    public function test_features_benefits_http_boundary_rejects_unknown_configuration_fields(): void
    {
        $admin =
            Admin::factory()->create([
                'role' => AdminRole::Editor,
            ]);

        $page =
            Page::factory()->create();

        $this
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
                    'type' => SectionType::FeaturesBenefits->value,

                    'template' => 'icon_grid',

                    'config' => [
                        'eyebrow' => null,

                        'heading' => 'Benefits',

                        'description' => 'Safe description.',

                        'items' => [
                            [
                                'title' => 'Fast Delivery',

                                'description' => 'Safe item.',

                                'icon' => 'truck',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => null,

                                'link_url' => null,

                                'unsafe_html' => '<script>alert(1)</script>',
                            ],
                        ],

                        'columns' => 3,

                        'alignment' => 'left',

                        'background_color' => '#ffffff',

                        'text_theme' => 'dark',

                        'custom_script' => 'alert(1)',
                    ],

                    'is_enabled' => true,
                ],
            )
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
