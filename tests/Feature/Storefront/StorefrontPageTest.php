<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageLayout;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Enums\SectionWidth;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_can_be_viewed_publicly(): void
    {
        $cmsPage = Page::factory()->create([
            'layout' => PageLayout::LeftSidebar,
            'show_breadcrumbs' => false,
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'content' => '<p>About our company.</p>',
            'published_at' => now()->subDay(),
            'meta_title' => 'About Our Company',
            'meta_description' => 'Learn more about us.',
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $cmsPage->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                function (Assert $page) use ($cmsPage): void {
                    $page
                        ->component(
                            'Frontend/Pages/Show',
                        )
                        ->where(
                            'page.id',
                            $cmsPage->id,
                        )
                        ->where(
                            'page.title',
                            'About Us',
                        )
                        ->where(
                            'page.slug',
                            'about-us',
                        )
                        ->where(
                            'page.layout',
                            PageLayout::LeftSidebar->value,
                        )
                        ->where(
                            'page.show_breadcrumbs',
                            false,
                        )
                        ->where(
                            'page.content_mode',
                            PageContentMode::Classic->value,
                        )
                        ->where(
                            'page.content',
                            '<p>About our company.</p>',
                        )
                        ->where(
                            'page.seo.meta_title',
                            'About Our Company',
                        )
                        ->where(
                            'page.seo.meta_description',
                            'Learn more about us.',
                        )
                        ->has(
                            'page.sections',
                            0,
                        );
                },
            );
    }

    public function test_draft_page_returns_not_found(): void
    {
        $page = Page::factory()->create([
            'slug' => 'draft-page',
            'status' => PageStatus::Draft,
            'content_mode' => PageContentMode::Classic,
        ]);

        $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        )->assertNotFound();
    }

    public function test_future_page_returns_not_found(): void
    {
        $page = Page::factory()->create([
            'slug' => 'future-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'published_at' => now()->addDay(),
        ]);

        $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        )->assertNotFound();
    }

    public function test_published_builder_page_exposes_static_hero_section(): void
    {
        $page = Page::factory()->create([
            'title' => 'Landing Page',
            'slug' => 'landing-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $section = $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'static',
            'position' => 10,
            'is_enabled' => true,
            'layout' => [
                'width' => SectionWidth::Full->value,
            ],
            'config' => [
                'autoplay' => false,
                'autoplay_delay' => 5000,
                'effect' => 'fade',
                'show_arrows' => false,
                'show_dots' => false,
                'slides' => [
                    [
                        'background_color' => '#111827',
                        'background_image' => null,
                        'top_title' => 'Welcome',
                        'title' => 'Build Your Store',
                        'description' => 'Everything you need in one place.',
                        'alignment' => 'center',
                        'primary_button' => [
                            'label' => 'Shop Now',
                            'url' => '/products',
                        ],
                        'secondary_button' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        'hero',
                    )
                    ->where(
                        'page.sections.0.template',
                        'static',
                    )
                    ->where(
                        'page.sections.0.layout.width',
                        SectionWidth::Full->value,
                    )
                    ->where(
                        'page.sections.0.config.slides.0.title',
                        'Build Your Store',
                    )
                    ->where(
                        'page.sections.0.config.slides.0.alignment',
                        'center',
                    )
                    ->where(
                        'page.sections.0.config.slides.0.primary_button.label',
                        'Shop Now',
                    )
                    ->where(
                        'page.sections.0.config.slides.0.primary_button.url',
                        '/products',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }

    public function test_published_builder_page_exposes_content_slider_hero_configuration(): void
    {
        $page = Page::factory()->create([
            'title' => 'Campaign',
            'slug' => 'campaign',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $section = $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'content_slider',
            'position' => 10,
            'is_enabled' => true,
            'layout' => [
                'width' => SectionWidth::Container->value,
            ],
            'config' => [
                'autoplay' => true,
                'autoplay_delay' => 4000,
                'effect' => 'slide_left',
                'show_arrows' => true,
                'show_dots' => true,
                'slides' => [
                    [
                        'background_color' => '#111827',
                        'background_image' => null,
                        'top_title' => 'Collection One',
                        'title' => 'First Slide',
                        'description' => 'First description.',
                        'alignment' => 'left',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                    [
                        'background_color' => '#1f2937',
                        'background_image' => null,
                        'top_title' => 'Collection Two',
                        'title' => 'Second Slide',
                        'description' => 'Second description.',
                        'alignment' => 'right',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        'hero',
                    )
                    ->where(
                        'page.sections.0.template',
                        'content_slider',
                    )
                    ->where(
                        'page.sections.0.layout.width',
                        SectionWidth::Container->value,
                    )
                    ->where(
                        'page.sections.0.config.autoplay',
                        true,
                    )
                    ->where(
                        'page.sections.0.config.autoplay_delay',
                        4000,
                    )
                    ->where(
                        'page.sections.0.config.effect',
                        'slide_left',
                    )
                    ->where(
                        'page.sections.0.config.show_arrows',
                        true,
                    )
                    ->where(
                        'page.sections.0.config.show_dots',
                        true,
                    )
                    ->has(
                        'page.sections.0.config.slides',
                        2,
                    )
                    ->where(
                        'page.sections.0.config.slides.0.title',
                        'First Slide',
                    )
                    ->where(
                        'page.sections.0.config.slides.1.title',
                        'Second Slide',
                    ),
            );
    }

    public function test_published_builder_page_exposes_image_slider_hero_configuration(): void
    {
        $page = Page::factory()->create([
            'title' => 'Offers',
            'slug' => 'offers',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $section = $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'image_slider',
            'position' => 10,
            'is_enabled' => true,
            'config' => [
                'autoplay' => true,
                'autoplay_delay' => 5000,
                'effect' => 'fade',
                'show_arrows' => true,
                'show_dots' => true,
                'slides' => [
                    [
                        'image' => '/storage/page-builder/hero/banner-one.jpg',
                        'alt' => 'Summer collection',
                        'url' => '/products',
                        'alignment' => 'center',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                    [
                        'image' => '/storage/page-builder/hero/banner-two.jpg',
                        'alt' => 'New arrivals',
                        'url' => null,
                        'alignment' => 'center',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.template',
                        'image_slider',
                    )
                    ->has(
                        'page.sections.0.config.slides',
                        2,
                    )
                    ->where(
                        'page.sections.0.config.slides.0.image',
                        '/storage/page-builder/hero/banner-one.jpg',
                    )
                    ->where(
                        'page.sections.0.config.slides.0.alt',
                        'Summer collection',
                    )
                    ->where(
                        'page.sections.0.config.slides.0.url',
                        '/products',
                    )
                    ->where(
                        'page.sections.0.config.slides.1.image',
                        '/storage/page-builder/hero/banner-two.jpg',
                    ),
            );
    }

    public function test_disabled_hero_section_is_not_exposed_publicly(): void
    {
        $page = Page::factory()->create([
            'title' => 'Hidden Hero',
            'slug' => 'hidden-hero',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'static',
            'position' => 10,
            'is_enabled' => false,
            'config' => [
                'autoplay' => false,
                'autoplay_delay' => 5000,
                'effect' => 'fade',
                'show_arrows' => false,
                'show_dots' => false,
                'slides' => [
                    [
                        'background_color' => '#111827',
                        'background_image' => null,
                        'top_title' => null,
                        'title' => 'Visitors must not see me',
                        'description' => null,
                        'alignment' => 'center',
                        'primary_button' => null,
                        'secondary_button' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->has(
                        'page.sections',
                        0,
                    ),
            );
    }

    public function test_builder_sections_are_exposed_in_position_order(): void
    {
        $page = Page::factory()->create([
            'title' => 'Ordered Page',
            'slug' => 'ordered-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $later = $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'static',
            'position' => 20,
            'is_enabled' => true,
            'config' => $this->staticHeroConfig(
                'Second Hero',
            ),
        ]);

        $earlier = $page->sections()->create([
            'type' => SectionType::Hero,
            'template' => 'static',
            'position' => 10,
            'is_enabled' => true,
            'config' => $this->staticHeroConfig(
                'First Hero',
            ),
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->has(
                        'page.sections',
                        2,
                    )
                    ->where(
                        'page.sections.0.id',
                        $earlier->id,
                    )
                    ->where(
                        'page.sections.1.id',
                        $later->id,
                    ),
            );
    }

    public function test_storefront_exposes_all_supported_page_layouts(): void
    {
        foreach (
            [
                PageLayout::FullWidth,
                PageLayout::LeftSidebar,
                PageLayout::RightSidebar,
            ] as $index => $layout
        ) {
            $page = Page::factory()->create([
                'layout' => $layout,
                'title' => 'Layout Page '.$index,
                'slug' => 'layout-page-'.$index,
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Classic,
                'published_at' => now()->subMinute(),
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
                    fn (Assert $inertia): Assert => $inertia
                        ->where(
                            'page.layout',
                            $layout->value,
                        ),
                );
        }
    }

    public function test_storefront_exposes_breadcrumb_visibility_setting(): void
    {
        foreach ([true, false] as $showBreadcrumbs) {
            $page = Page::factory()->create([
                'show_breadcrumbs' => $showBreadcrumbs,
                'title' => $showBreadcrumbs
                    ? 'Breadcrumb Page'
                    : 'No Breadcrumb Page',
                'slug' => $showBreadcrumbs
                    ? 'breadcrumb-page'
                    : 'no-breadcrumb-page',
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Classic,
                'published_at' => now()->subMinute(),
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
                    fn (Assert $inertia): Assert => $inertia
                        ->where(
                            'page.show_breadcrumbs',
                            $showBreadcrumbs,
                        ),
                );
        }
    }

    private function staticHeroConfig(
        string $title,
    ): array {
        return [
            'autoplay' => false,
            'autoplay_delay' => 5000,
            'effect' => 'fade',
            'show_arrows' => false,
            'show_dots' => false,
            'slides' => [
                [
                    'background_color' => '#111827',
                    'background_image' => null,
                    'top_title' => null,
                    'title' => $title,
                    'description' => null,
                    'alignment' => 'center',
                    'primary_button' => null,
                    'secondary_button' => null,
                ],
            ],
        ];
    }
}
