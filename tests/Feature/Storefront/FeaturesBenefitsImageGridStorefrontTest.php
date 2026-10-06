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

final class FeaturesBenefitsImageGridStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_image_grid_configuration(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Our Benefits',

                'slug' => 'our-benefits',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::FeaturesBenefits,

                    'template' => 'image_grid',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => [
                        'eyebrow' => 'Why choose us',

                        'heading' => 'What makes us different',

                        'description' => 'Services designed around a better shopping experience.',

                        'items' => [
                            [
                                'title' => 'Fast Delivery',

                                'description' => 'Reliable delivery for your orders.',

                                'icon' => null,

                                'image' => '/storage/page-builder/features/delivery.webp',

                                'image_alt' => 'A delivery package ready for dispatch',

                                'link_label' => 'Delivery details',

                                'link_url' => '/shipping',
                            ],

                            [
                                'title' => 'Product Quality',

                                'description' => 'Products selected with care.',

                                'icon' => null,

                                'image' => '/storage/page-builder/features/quality.webp',

                                'image_alt' => 'Selected quality products',

                                'link_label' => null,

                                'link_url' => null,
                            ],

                            [
                                'title' => 'Customer Support',

                                'description' => 'Helpful support when you need it.',

                                'icon' => null,

                                'image' => '/storage/page-builder/features/support.webp',

                                'image_alt' => 'Customer support representative',

                                'link_label' => null,

                                'link_url' => null,
                            ],
                        ],

                        'columns' => 3,

                        'alignment' => 'left',

                        'background_color' => '#f8fafc',

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
                        'image_grid',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'What makes us different',
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
                        'page.sections.0.config.background_color',
                        '#f8fafc',
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
                        'page.sections.0.config.items.0.image',
                        '/storage/page-builder/features/delivery.webp',
                    )
                    ->where(
                        'page.sections.0.config.items.0.image_alt',
                        'A delivery package ready for dispatch',
                    )
                    ->where(
                        'page.sections.0.config.items.0.link_url',
                        '/shipping',
                    )
                    ->where(
                        'page.sections.0.config.items.1.image',
                        '/storage/page-builder/features/quality.webp',
                    )
                    ->where(
                        'page.sections.0.config.items.2.image',
                        '/storage/page-builder/features/support.webp',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
