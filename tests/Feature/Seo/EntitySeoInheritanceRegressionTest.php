<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EntitySeoInheritanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_robot_values_remain_inheritance_values(): void
    {
        $product =
            Product::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/products/test',

                robotsIndex: null,

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
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'robots_index' => null,

                'robots_follow' => null,
            ],
        );
    }

    public function test_explicit_false_robot_values_are_not_collapsed_to_null(): void
    {
        $product =
            Product::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: false,

                robotsFollow: false,

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
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'robots_index' => false,

                'robots_follow' => false,
            ],
        );
    }

    public function test_explicit_true_robot_values_are_preserved(): void
    {
        $product =
            Product::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: true,

                robotsFollow: true,

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
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'robots_index' => true,

                'robots_follow' => true,
            ],
        );
    }

    public function test_empty_override_data_removes_existing_seo_row(): void
    {
        $product =
            Product::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/custom',

                robotsIndex: false,

                robotsFollow: true,

                ogTitle: 'Existing OG title',

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
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );

        $repository->save(
            $product,
            EntitySeoData::empty(),
        );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );
    }

    public function test_social_image_urls_are_persisted_as_overrides(): void
    {
        $product =
            Product::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: null,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: 'https://example.com/storage/seo/social/og.jpg',

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: 'https://example.com/storage/seo/social/twitter.jpg',
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'og_image' => 'https://example.com/storage/seo/social/og.jpg',

                'twitter_image' => 'https://example.com/storage/seo/social/twitter.jpg',
            ],
        );
    }

    public function test_shared_seo_title_and_description_remain_null_for_catalog_entities(): void
    {
        $repository =
            app(
                EntitySeoRepository::class,
            );

        $entities = [
            Product::factory()
                ->create([
                    'meta_title' => 'Product SEO Title',

                    'meta_description' => 'Product SEO Description',
                ]),

            Category::factory()
                ->create([
                    'meta_title' => 'Category SEO Title',

                    'meta_description' => 'Category SEO Description',
                ]),

            Brand::factory()
                ->create([
                    'meta_title' => 'Brand SEO Title',

                    'meta_description' => 'Brand SEO Description',
                ]),

            Page::factory()
                ->create([
                    'meta_title' => 'Page SEO Title',

                    'meta_description' => 'Page SEO Description',
                ]),
        ];

        foreach (
            $entities as $entity
        ) {
            $repository->save(
                $entity,
                new EntitySeoData(
                    title: null,

                    description: null,

                    canonicalUrl: 'https://example.com/custom',

                    robotsIndex: null,

                    robotsFollow: null,

                    ogTitle: 'Social title',

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
                    'seoable_type' => $entity::class,

                    'seoable_id' => $entity->getKey(),

                    'title' => null,

                    'description' => null,
                ],
            );
        }
    }

    public function test_each_entity_has_independent_polymorphic_seo_metadata(): void
    {
        $repository =
            app(
                EntitySeoRepository::class,
            );

        $product =
            Product::factory()
                ->create();

        $brand =
            Brand::factory()
                ->create();

        $repository->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/product',

                robotsIndex: null,

                robotsFollow: null,

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
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/brand',

                robotsIndex: null,

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
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'canonical_url' => 'https://example.com/product',
            ],
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'canonical_url' => 'https://example.com/brand',
            ],
        );
    }
}
