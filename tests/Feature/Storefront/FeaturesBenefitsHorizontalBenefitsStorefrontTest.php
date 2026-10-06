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

final class FeaturesBenefitsHorizontalBenefitsStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_horizontal_benefits_configuration(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Shopping Benefits',

                'slug' => 'shopping-benefits',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'horizontal_benefits',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => [
                        'eyebrow' => null,

                        'heading' => 'Shopping made simple',

                        'description' => 'Helpful services with every order.',

                        'items' => [
                            [
                                'title' => 'Fast Delivery',

                                'description' => 'Quick and dependable delivery.',

                                'icon' => 'truck',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => 'Delivery details',

                                'link_url' => '/shipping',
                            ],

                            [
                                'title' => 'Secure Payments',

                                'description' => 'Protected checkout experience.',

                                'icon' => 'shield-check',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => null,

                                'link_url' => null,
                            ],

                            [
                                'title' => 'Helpful Support',

                                'description' => 'Support when you need it.',

                                'icon' => 'headphones',

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
                ) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        1,
                    )
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::FeaturesBenefits->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'horizontal_benefits',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Shopping made simple',
                    )
                    ->where(
                        'page.sections.0.config.columns',
                        3,
                    )
                    ->where(
                        'page.sections.0.config.alignment',
                        'left',
                    )
                    ->where(
                        'page.sections.0.config.text_theme',
                        'dark',
                    )
                    ->has(
                        'page.sections.0.config.items',
                        3,
                    )
                    ->where(
                        'page.sections.0.config.items.0.title',
                        'Fast Delivery',
                    )
                    ->where(
                        'page.sections.0.config.items.0.icon',
                        'truck',
                    )
                    ->where(
                        'page.sections.0.config.items.0.link_url',
                        '/shipping',
                    )
                    ->where(
                        'page.sections.0.config.items.1.icon',
                        'shield-check',
                    )
                    ->where(
                        'page.sections.0.config.items.2.icon',
                        'headphones',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
