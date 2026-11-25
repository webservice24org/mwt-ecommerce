<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Contracts\Seoable;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_implements_shared_seo_contract(): void
    {
        $brand =
            Brand::factory()
                ->create();

        $this->assertInstanceOf(
            Seoable::class,
            $brand,
        );

        $this->assertNull(
            $brand
                ->seoMeta()
                ->first(),
        );
    }

    public function test_brand_can_persist_extended_seo_metadata(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'meta_title' => 'Apple Products',

                    'meta_description' => 'Shop Apple products.',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $brand,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/brands/apple',

                robotsIndex: true,

                robotsFollow: true,

                ogTitle: 'Apple Brand',

                ogDescription: 'Shop Apple products.',

                ogImage: 'https://example.com/images/apple.jpg',

                twitterTitle: 'Apple Products',

                twitterDescription: 'Shop Apple products.',

                twitterImage: 'https://example.com/images/apple-x.jpg',
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/brands/apple',

                'robots_index' => true,

                'robots_follow' => true,
            ],
        );

        $brand->refresh();

        $this->assertSame(
            'Apple Products',
            $brand->meta_title,
        );

        $this->assertSame(
            'Shop Apple products.',
            $brand->meta_description,
        );
    }

    public function test_brand_meta_fields_are_not_duplicated_into_seo_meta(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'meta_title' => 'Brand Meta',

                    'meta_description' => 'Brand Description',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $brand,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: false,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'title' => null,

                'description' => null,

                'robots_index' => false,
            ],
        );
    }

    public function test_explicit_empty_brand_seo_removes_override_row(): void
    {
        $brand =
            Brand::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $brand,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/brands/custom',

                robotsIndex: false,

                robotsFollow: true,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $repository->save(
            $brand,
            EntitySeoData::empty(),
        );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,
            ],
        );
    }
}
