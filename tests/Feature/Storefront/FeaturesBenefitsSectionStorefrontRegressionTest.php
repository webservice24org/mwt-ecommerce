<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FeaturesBenefitsSectionStorefrontRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_all_three_features_benefits_templates_in_order(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Benefits',

                'slug' => 'benefits',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $iconGrid =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'icon_grid',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => $this->iconGridConfig(),
                ]);

        $imageGrid =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'image_grid',

                    'position' => 20,

                    'is_enabled' => true,

                    'config' => $this->imageGridConfig(),
                ]);

        $horizontal =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'horizontal_benefits',

                    'position' => 30,

                    'is_enabled' => true,

                    'config' => $this->horizontalConfig(),
                ]);

        $response =
            $this->get(
                route(
                    'frontend.pages.show',
                    $page->slug,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $inertia,
                ): Assert => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        3,
                    )
                    ->where(
                        'page.sections.0.id',
                        $iconGrid->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::FeaturesBenefits->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'icon_grid',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Icon Benefits',
                    )
                    ->where(
                        'page.sections.0.config.items.0.icon',
                        'truck',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    )
                    ->where(
                        'page.sections.1.id',
                        $imageGrid->id,
                    )
                    ->where(
                        'page.sections.1.template',
                        'image_grid',
                    )
                    ->where(
                        'page.sections.1.config.heading',
                        'Image Benefits',
                    )
                    ->where(
                        'page.sections.1.config.items.0.image',
                        '/storage/page-builder/features/delivery.webp',
                    )
                    ->where(
                        'page.sections.1.config.items.0.image_alt',
                        'Delivery package',
                    )
                    ->where(
                        'page.sections.1.data',
                        [],
                    )
                    ->where(
                        'page.sections.2.id',
                        $horizontal->id,
                    )
                    ->where(
                        'page.sections.2.template',
                        'horizontal_benefits',
                    )
                    ->where(
                        'page.sections.2.config.heading',
                        'Horizontal Benefits',
                    )
                    ->where(
                        'page.sections.2.config.items.0.icon',
                        'shield-check',
                    )
                    ->where(
                        'page.sections.2.config.items.0.link_url',
                        '#security',
                    )
                    ->where(
                        'page.sections.2.data',
                        [],
                    ),
            );
    }

    public function test_disabled_features_benefits_section_is_not_exposed_publicly(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Hidden Benefits',

                'slug' => 'hidden-benefits',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::FeaturesBenefits,

                'template' => 'icon_grid',

                'position' => 10,

                'is_enabled' => false,

                'config' => $this->iconGridConfig(),
            ]);

        $this
            ->get(
                route(
                    'frontend.pages.show',
                    $page->slug,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $inertia,
                ): Assert => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        0,
                    ),
            );
    }

    public function test_features_benefits_section_remains_config_only_on_storefront(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Config Only Benefits',

                'slug' => 'config-only-benefits',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::FeaturesBenefits,

                'template' => 'icon_grid',

                'position' => 10,

                'is_enabled' => true,

                'config' => $this->iconGridConfig(),
            ]);

        $this
            ->get(
                route(
                    'frontend.pages.show',
                    $page->slug,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $inertia,
                ): Assert => $inertia
                    ->has(
                        'page.sections',
                        1,
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function iconGridConfig(): array
    {
        return [
            'eyebrow' => 'Why choose us',

            'heading' => 'Icon Benefits',

            'description' => 'Icon-based benefits.',

            'items' => [
                [
                    'title' => 'Fast Delivery',

                    'description' => 'Fast and dependable delivery.',

                    'icon' => 'truck',

                    'image' => null,

                    'image_alt' => null,

                    'link_label' => 'Shipping',

                    'link_url' => '/shipping',
                ],
            ],

            'columns' => 3,

            'alignment' => 'center',

            'background_color' => '#ffffff',

            'text_theme' => 'dark',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageGridConfig(): array
    {
        return [
            'eyebrow' => 'Visual benefits',

            'heading' => 'Image Benefits',

            'description' => 'Image-based benefits.',

            'items' => [
                [
                    'title' => 'Delivery',

                    'description' => 'Your order on the way.',

                    'icon' => null,

                    'image' => '/storage/page-builder/features/delivery.webp',

                    'image_alt' => 'Delivery package',

                    'link_label' => null,

                    'link_url' => null,
                ],
            ],

            'columns' => 3,

            'alignment' => 'left',

            'background_color' => '#f8fafc',

            'text_theme' => 'dark',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function horizontalConfig(): array
    {
        return [
            'eyebrow' => null,

            'heading' => 'Horizontal Benefits',

            'description' => 'Important benefits at a glance.',

            'items' => [
                [
                    'title' => 'Secure Shopping',

                    'description' => 'Protected checkout experience.',

                    'icon' => 'shield-check',

                    'image' => null,

                    'image_alt' => null,

                    'link_label' => 'Security',

                    'link_url' => '#security',
                ],
            ],

            'columns' => 3,

            'alignment' => 'left',

            'background_color' => '#0f172a',

            'text_theme' => 'light',
        ];
    }
}
