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

final class BrandLogoStripStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_strip_is_exposed_to_published_storefront_page(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Brands',

                'slug' => 'brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'Nordica',

                'slug' => 'nordica',

                'logo_path' => null,

                'is_active' => true,

                'position' => 10,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Atelier',

                'slug' => 'atelier',

                'logo_path' => null,

                'is_active' => true,

                'position' => 20,
            ]);

        Brand::factory()->create([
            'name' => 'Inactive',

            'is_active' => false,

            'position' => 0,
        ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::Brands,

                    'template' => 'logo_strip',

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
                        'logo_strip',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Trusted by Leading Brands',
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
                        'Nordica',
                    )
                    ->where(
                        'page.sections.0.data.1.id',
                        $second->id,
                    )
                    ->where(
                        'page.sections.0.data.1.name',
                        'Atelier',
                    ),
            );
    }
}
