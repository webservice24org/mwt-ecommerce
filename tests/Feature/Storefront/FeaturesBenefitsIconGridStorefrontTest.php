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

final class FeaturesBenefitsIconGridStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_icon_grid_configuration(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Why Shop With Us',

                'slug' => 'why-shop-with-us',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'icon_grid',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => [
                        'eyebrow' => 'Why choose us',

                        'heading' => 'Benefits built around your shopping experience',

                        'description' => 'Fast, secure, and dependable shopping.',

                        'items' => [
                            [
                                'title' => 'Fast Delivery',

                                'description' => 'Reliable delivery for every order.',

                                'icon' => 'truck',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => 'Delivery details',

                                'link_url' => '/shipping',
                            ],

                            [
                                'title' => 'Secure Payments',

                                'description' => 'Shop with confidence.',

                                'icon' => 'shield-check',

                                'image' => null,

                                'image_alt' => null,

                                'link_label' => null,

                                'link_url' => null,
                            ],

                            [
                                'title' => 'Helpful Support',

                                'description' => 'Our team is here to help.',

                                'icon' => 'headphones',

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
                        'icon_grid',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Benefits built around your shopping experience',
                    )
                    ->where(
                        'page.sections.0.config.columns',
                        3,
                    )
                    ->where(
                        'page.sections.0.config.alignment',
                        'center',
                    )
                    ->where(
                        'page.sections.0.config.background_color',
                        '#ffffff',
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
