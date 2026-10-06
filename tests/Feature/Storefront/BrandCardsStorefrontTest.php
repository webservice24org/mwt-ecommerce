<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\BrandSection;
use App\Models\Brand;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BrandCardsStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_cards_are_exposed_to_published_storefront_page(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Featured Brands',

                'slug' => 'featured-brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $brand =
            Brand::factory()->create([
                'name' => 'Acme',

                'slug' => 'acme',

                'description' => 'Premium products for modern customers.',

                'logo_path' => null,

                'is_active' => true,

                'position' => 10,
            ]);

        Product::factory()
            ->published()
            ->count(2)
            ->create([
                'brand_id' => $brand->id,
            ]);

        /*
         * Draft products must not contribute
         * to the public Brand product count.
         */
        Product::factory()->create([
            'brand_id' => $brand->id,
        ]);

        /*
         * A published product scheduled for
         * the future must also be excluded.
         */
        Product::factory()
            ->published()
            ->create([
                'brand_id' => $brand->id,

                'published_at' => now()->addDay(),
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'brand_cards',
            );

        $config[
            'view_all_label'
        ] =
            'View All Brands';

        $config[
            'view_all_url'
        ] =
            '/products';

        $section =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::Brands,

                    'template' => 'brand_cards',

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
                        'brand_cards',
                    )
                    ->where(
                        'page.sections.0.config.eyebrow',
                        'Official Partners',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Shop Top Featured Brands',
                    )
                    ->where(
                        'page.sections.0.config.show_name',
                        true,
                    )
                    ->where(
                        'page.sections.0.config.show_description',
                        true,
                    )
                    ->where(
                        'page.sections.0.config.show_product_count',
                        true,
                    )
                    ->where(
                        'page.sections.0.config.view_all_label',
                        'View All Brands',
                    )
                    ->where(
                        'page.sections.0.config.view_all_url',
                        '/products',
                    )
                    ->has(
                        'page.sections.0.data',
                        1,
                    )
                    ->where(
                        'page.sections.0.data.0.id',
                        $brand->id,
                    )
                    ->where(
                        'page.sections.0.data.0.name',
                        'Acme',
                    )
                    ->where(
                        'page.sections.0.data.0.slug',
                        'acme',
                    )
                    ->where(
                        'page.sections.0.data.0.description',
                        'Premium products for modern customers.',
                    )
                    ->where(
                        'page.sections.0.data.0.logo_url',
                        null,
                    )
                    ->where(
                        'page.sections.0.data.0.product_count',
                        2,
                    ),
            );
    }

    public function test_brand_cards_do_not_expose_inactive_brands(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Brand Directory',

                'slug' => 'brand-directory',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $active =
            Brand::factory()->create([
                'name' => 'Active Brand',

                'slug' => 'active-brand',

                'is_active' => true,

                'position' => 20,
            ]);

        Brand::factory()->create([
            'name' => 'Inactive Brand',

            'slug' => 'inactive-brand',

            'is_active' => false,

            'position' => 10,
        ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'brand_cards',
            );

        $page
            ->sections()
            ->create([
                'type' => SectionType::Brands,

                'template' => 'brand_cards',

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
                        'page.sections',
                        1,
                    )
                    ->has(
                        'page.sections.0.data',
                        1,
                    )
                    ->where(
                        'page.sections.0.data.0.id',
                        $active->id,
                    )
                    ->where(
                        'page.sections.0.data.0.name',
                        'Active Brand',
                    ),
            );
    }

    public function test_manual_brand_cards_preserve_selected_order(): void
    {
        $page =
            Page::factory()->create([
                'title' => 'Selected Brands',

                'slug' => 'selected-brands',

                'status' => PageStatus::Published,

                'content_mode' => PageContentMode::Builder,

                'published_at' => now()->subMinute(),
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'First Brand',

                'slug' => 'first-brand',

                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Second Brand',

                'slug' => 'second-brand',

                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'brand_cards',
            );

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $second->id,
                $first->id,
            ],
        ];

        $page
            ->sections()
            ->create([
                'type' => SectionType::Brands,

                'template' => 'brand_cards',

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
