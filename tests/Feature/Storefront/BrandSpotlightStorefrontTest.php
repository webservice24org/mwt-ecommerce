<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\BrandSection;
use App\Models\Brand;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BrandSpotlightStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_spotlight_is_exposed_to_published_storefront_page(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Luxury Brands',

                'slug' => 'luxury-brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'Prada',

                'slug' => 'prada',

                'logo_path' => null,

                'is_active' => true,

                'position' => 10,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Gucci',

                'slug' => 'gucci',

                'logo_path' => null,

                'is_active' => true,

                'position' => 20,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'spotlight_banner',
            );

        $config[
            'primary_button_label'
        ] =
            'Browse Brand Directory';

        $config[
            'primary_button_url'
        ] =
            '/products';

        $config[
            'secondary_button_label'
        ] =
            'Become a Partner Brand';

        $config[
            'secondary_button_url'
        ] =
            '/contact';

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::Brands,

                    'template' => 'spotlight_banner',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => $config,
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
                        1,
                    )
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::Brands->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'spotlight_banner',
                    )
                    ->where(
                        'page.sections.0.config.eyebrow',
                        'Brand Spotlight',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Partnered with Leading Global Brands',
                    )
                    ->where(
                        'page.sections.0.config.primary_button_label',
                        'Browse Brand Directory',
                    )
                    ->where(
                        'page.sections.0.config.primary_button_url',
                        '/products',
                    )
                    ->where(
                        'page.sections.0.config.secondary_button_label',
                        'Become a Partner Brand',
                    )
                    ->where(
                        'page.sections.0.config.secondary_button_url',
                        '/contact',
                    )
                    ->where(
                        'page.sections.0.config.show_name',
                        true,
                    )
                    ->has(
                        'page.sections.0.data',
                        2,
                    )
                    ->where(
                        'page.sections.0.data.0.id',
                        $first->id,
                    )
                    ->where(
                        'page.sections.0.data.0.name',
                        'Prada',
                    )
                    ->where(
                        'page.sections.0.data.1.id',
                        $second->id,
                    )
                    ->where(
                        'page.sections.0.data.1.name',
                        'Gucci',
                    ),
            );
    }

    public function test_manual_spotlight_preserves_selected_brand_order(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Selected Luxury Brands',

                'slug' => 'selected-luxury-brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'First Brand',

                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Second Brand',

                'is_active' => true,
            ]);

        $inactive =
            Brand::factory()->create([
                'name' => 'Inactive Brand',

                'is_active' => false,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'spotlight_banner',
            );

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $second->id,
                $inactive->id,
                $first->id,
            ],
        ];

        $page
            ->sections()
            ->create([
                'type' => SectionType::Brands,

                'template' => 'spotlight_banner',

                'position' => 10,

                'is_enabled' => true,

                'config' => $config,
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
                        'page.sections.0.data',
                        2,
                    )
                    ->where(
                        'page.sections.0.data.0.id',
                        $second->id,
                    )
                    ->where(
                        'page.sections.0.data.1.id',
                        $first->id,
                    ),
            );
    }
}
