<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Domain\PageBuilder\Sections\BrandSection;
use App\Models\Brand;
use App\Models\PageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandPageSectionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_brand_source_resolves_only_active_brands_in_catalog_order(): void
    {
        $later =
            Brand::factory()->create([
                'name' => 'Later Brand',

                'slug' => 'later-brand',

                'logo_path' => null,

                'is_active' => true,

                'position' => 20,
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'First Brand',

                'slug' => 'first-brand',

                'logo_path' => null,

                'is_active' => true,

                'position' => 10,
            ]);

        Brand::factory()->create([
            'name' => 'Hidden Brand',

            'is_active' => false,

            'position' => 0,
        ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $config['limit'] =
            2;

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            901;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            901,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::Brands,
            $resolved->type,
        );

        $this->assertSame(
            'logo_strip',
            $resolved->template,
        );

        $this->assertCount(
            2,
            $resolved->data,
        );

        $this->assertSame(
            $first->id,
            $resolved
                ->data[0]['id'],
        );

        $this->assertSame(
            $later->id,
            $resolved
                ->data[1]['id'],
        );
    }

    public function test_manual_brand_source_preserves_configured_order_and_skips_inactive_brands(): void
    {
        $first =
            Brand::factory()->create([
                'name' => 'First',

                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Second',

                'is_active' => true,
            ]);

        $inactive =
            Brand::factory()->create([
                'name' => 'Inactive',

                'is_active' => false,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $second->id,
                $inactive->id,
                $first->id,
            ],
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            902;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [
                $second->id,
                $first->id,
            ],
            array_column(
                $resolved->data,
                'id',
            ),
        );
    }

    public function test_brand_resolver_exposes_logo_strip_data_contract(): void
    {
        $brand =
            Brand::factory()->create([
                'name' => 'Acme',

                'slug' => 'acme',

                'description' => 'Premium products.',

                'logo_path' => null,

                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $brand->id,
            ],
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id = 903;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [
                [
                    'id' => $brand->id,

                    'name' => 'Acme',

                    'slug' => 'acme',

                    'description' => 'Premium products.',

                    'logo_url' => null,

                    'product_count' => 0,
                ],
            ],
            $resolved->data,
        );
    }

    public function test_manual_brand_limit_is_filled_after_inactive_brands_are_skipped(): void
    {
        $inactive =
            Brand::factory()->create([
                'name' => 'Inactive',

                'is_active' => false,
            ]);

        $first =
            Brand::factory()->create([
                'name' => 'First Active',

                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'name' => 'Second Active',

                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $config['limit'] =
            2;

        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                $inactive->id,
                $first->id,
                $second->id,
            ],
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id = 904;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [
                $first->id,
                $second->id,
            ],
            array_column(
                $resolved->data,
                'id',
            ),
        );
    }

    public function test_brand_resolver_counts_only_published_products(): void
    {
        $brand =
            Brand::factory()->create([
                'name' => 'Acme',

                'slug' => 'acme',

                'is_active' => true,
            ]);

        Product::factory()
            ->published()
            ->count(2)
            ->create([
                'brand_id' => $brand->id,
            ]);

        Product::factory()->create([
            'brand_id' => $brand->id,
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
                $brand->id,
            ],
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'brand_cards',

                'config' => $config,
            ]);

        $section->id =
            905;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            2,
            $resolved
                ->data[0]['product_count'],
        );
    }

    public function test_invalid_brand_source_type_fails_closed(): void
    {
        Brand::factory()
            ->count(3)
            ->create([
                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        /*
         * Simulate corrupted/legacy database data
         * bypassing normal schema validation.
         */
        $config['source'] = [
            'type' => 'unexpected-source',
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            906;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }

    public function test_missing_brand_source_type_fails_closed(): void
    {
        Brand::factory()
            ->count(2)
            ->create([
                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        $config['source'] =
            [];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            907;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }

    public function test_manual_brand_source_rejects_non_list_ids(): void
    {
        $first =
            Brand::factory()->create([
                'is_active' => true,
            ]);

        $second =
            Brand::factory()->create([
                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        /*
         * Simulate malformed database JSON.
         */
        $config['source'] = [
            'type' => 'manual',

            'brand_ids' => [
                'first' => $first->id,

                'second' => $second->id,
            ],
        ];

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            908;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }

    public function test_brand_resolver_defensively_caps_limit_at_twenty_four(): void
    {
        Brand::factory()
            ->count(30)
            ->create([
                'is_active' => true,
            ]);

        $config =
            (
                new BrandSection
            )->defaultConfigForTemplate(
                'logo_strip',
            );

        /*
         * Simulate persisted data that bypassed
         * the schema.
         */
        $config['limit'] =
            999;

        $section =
            new PageSection([
                'type' => SectionType::Brands,

                'template' => 'logo_strip',

                'config' => $config,
            ]);

        $section->id =
            909;

        $resolved =
            app(
                PageSectionResolver::class,
            )->resolve(
                $section,
            );

        $this->assertCount(
            24,
            $resolved->data,
        );
    }
}
