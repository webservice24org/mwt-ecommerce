<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontHomepageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_falls_back_when_no_builder_homepage_exists(): void
    {
        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Home',
                    )
                    ->where(
                        'builderPage',
                        null,
                    )
                    ->has('home'),
            );
    }

    public function test_published_builder_homepage_is_exposed_to_home(): void
    {
        $homepage = Page::factory()
            ->create([
                'type' => PageType::Home,
                'title' => 'Store Homepage',
                'slug' => 'home',
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Builder,
                'published_at' => now()->subMinute(),
                'meta_title' => 'MWT Store',
                'meta_description' => 'Welcome to our store.',
            ]);

        $section = $homepage
            ->sections()
            ->create([
                'type' => SectionType::Hero,
                'template' => 'static',
                'position' => 10,
                'is_enabled' => true,
                'config' => $this->heroConfig(),
            ]);

        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Home',
                    )
                    ->where(
                        'builderPage.id',
                        $homepage->id,
                    )
                    ->where(
                        'builderPage.title',
                        'Store Homepage',
                    )
                    ->where(
                        'builderPage.meta_title',
                        'MWT Store',
                    )
                    ->where(
                        'builderPage.meta_description',
                        'Welcome to our store.',
                    )
                    ->has(
                        'builderPage.sections',
                        1,
                    )
                    ->where(
                        'builderPage.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'builderPage.sections.0.type',
                        'hero',
                    )
                    ->where(
                        'builderPage.sections.0.template',
                        'static',
                    )
                    ->where(
                        'builderPage.sections.0.config.slides.0.title',
                        'Builder Homepage Hero',
                    ),
            );
    }

    public function test_draft_builder_homepage_is_not_exposed(): void
    {
        Page::factory()->create([
            'type' => PageType::Home,
            'status' => PageStatus::Draft,
            'content_mode' => PageContentMode::Builder,
        ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'builderPage',
                        null,
                    ),
            );
    }

    public function test_disabled_homepage_sections_are_not_exposed(): void
    {
        $homepage = Page::factory()
            ->create([
                'type' => PageType::Home,
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Builder,
                'published_at' => now()->subMinute(),
            ]);

        $homepage
            ->sections()
            ->create([
                'type' => SectionType::Hero,
                'template' => 'static',
                'position' => 10,
                'is_enabled' => false,
                'config' => $this->heroConfig(),
            ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'builderPage.sections',
                        0,
                    ),
            );
    }

    public function test_homepage_sections_are_resolved_in_position_order(): void
    {
        $homepage = Page::factory()
            ->create([
                'type' => PageType::Home,
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Builder,
                'published_at' => now()->subMinute(),
            ]);

        $later = $homepage
            ->sections()
            ->create([
                'type' => SectionType::Hero,
                'template' => 'static',
                'position' => 20,
                'is_enabled' => true,
                'config' => $this->heroConfig(
                    'Second',
                ),
            ]);

        $earlier = $homepage
            ->sections()
            ->create([
                'type' => SectionType::Hero,
                'template' => 'static',
                'position' => 10,
                'is_enabled' => true,
                'config' => $this->heroConfig(
                    'First',
                ),
            ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'builderPage.sections',
                        2,
                    )
                    ->where(
                        'builderPage.sections.0.id',
                        $earlier->id,
                    )
                    ->where(
                        'builderPage.sections.1.id',
                        $later->id,
                    ),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function heroConfig(
        string $title =
            'Builder Homepage Hero',
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
                    'top_title' => 'Welcome',
                    'title' => $title,
                    'description' => 'Builder homepage.',
                    'alignment' => 'center',
                    'primary_button' => null,
                    'secondary_button' => null,
                ],
            ],
        ];
    }

    public function test_builder_homepage_resolves_dynamic_section_data(): void
    {
        $homepage = Page::factory()
            ->create([
                'type' => PageType::Home,
                'status' => PageStatus::Published,
                'content_mode' => PageContentMode::Builder,
                'published_at' => now()->subMinute(),
            ]);

        $homepage
            ->sections()
            ->create([
                'type' => SectionType::FeaturedProducts,
                'template' => 'grid',
                'position' => 10,
                'is_enabled' => true,
                'config' => [
                    'title' => 'Homepage Products',
                    'limit' => 8,
                    'source' => [
                        'type' => 'featured',
                    ],
                ],
            ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'builderPage.sections.0.type',
                        'featured_products',
                    )
                    ->where(
                        'builderPage.sections.0.template',
                        'grid',
                    )
                    ->where(
                        'builderPage.sections.0.config.title',
                        'Homepage Products',
                    )
                    ->has(
                        'builderPage.sections.0.data.products',
                    ),
            );
    }
}
