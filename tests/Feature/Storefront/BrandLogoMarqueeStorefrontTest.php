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

final class BrandLogoMarqueeStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_marquee_is_exposed_to_published_storefront_page(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Global Brands',

                'slug' => 'global-brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'Adidas',

                'slug' => 'adidas',

                'logo_path' => null,

                'is_active' => true,

                'position' => 10,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Chanel',

                'slug' => 'chanel',

                'logo_path' => null,

                'is_active' => true,

                'position' => 20,
            ]);

        Brand::factory()->create([
            'name' => 'Hidden Brand',

            'slug' => 'hidden-brand',

            'is_active' => false,

            'position' => 0,
        ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_marquee',
            );

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::Brands,

                    'template' => 'logo_marquee',

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
                        'logo_marquee',
                    )
                    ->where(
                        'page.sections.0.config.eyebrow',
                        'Our Retail Network',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Featured in Global Stores',
                    )
                    ->where(
                        'page.sections.0.config.marquee_duration',
                        25,
                    )
                    ->where(
                        'page.sections.0.config.pause_on_hover',
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
                        'Adidas',
                    )
                    ->where(
                        'page.sections.0.data.1.id',
                        $second->id,
                    )
                    ->where(
                        'page.sections.0.data.1.name',
                        'Chanel',
                    ),
            );
    }

    public function test_manual_logo_marquee_preserves_brand_order(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Selected Global Brands',

                'slug' => 'selected-global-brands',

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

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_marquee',
            );

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $second->id,
                $first->id,
            ],
        ];

        $config[
            'marquee_duration'
        ] =
            40;

        $config[
            'pause_on_hover'
        ] =
            false;

        $page
            ->sections()
            ->create([
                'type' => SectionType::Brands,

                'template' => 'logo_marquee',

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
                    ->where(
                        'page.sections.0.config.marquee_duration',
                        40,
                    )
                    ->where(
                        'page.sections.0.config.pause_on_hover',
                        false,
                    )
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
