<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Contracts\Seoable;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_implements_shared_seo_contract(): void
    {
        $product =
            Product::factory()
                ->create();

        $this->assertInstanceOf(
            Seoable::class,
            $product,
        );

        $this->assertNull(
            $product
                ->seoMeta()
                ->first(),
        );
    }

    public function test_product_can_persist_extended_seo_metadata(): void
    {
        $product =
            Product::factory()
                ->create();

        $saved =
            app(
                EntitySeoRepository::class,
            )->save(
                $product,
                new EntitySeoData(
                    /*
                     * Product title and description remain
                     * owned by the products table.
                     */
                    title: null,

                    description: null,

                    canonicalUrl: 'https://example.com/products/custom-product',

                    robotsIndex: false,

                    robotsFollow: true,

                    ogTitle: 'Custom OG Product',

                    ogDescription: 'Custom social description.',

                    ogImage: 'https://example.com/images/product-og.jpg',

                    twitterTitle: 'Custom X Product',

                    twitterDescription: 'Custom X description.',

                    twitterImage: 'https://example.com/images/product-x.jpg',
                ),
            );

        $this->assertSame(
            'https://example.com/products/custom-product',
            $saved->canonicalUrl,
        );

        $this->assertFalse(
            $saved->robotsIndex,
        );

        $this->assertTrue(
            $saved->robotsFollow,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/products/custom-product',

                'robots_index' => false,

                'robots_follow' => true,
            ],
        );
    }

    public function test_product_existing_meta_title_and_description_are_not_duplicated(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Existing Product SEO Title',

                    'meta_description' => 'Existing Product SEO Description',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $product,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/products/product',

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

        $product->refresh();

        $this->assertSame(
            'Existing Product SEO Title',
            $product->meta_title,
        );

        $this->assertSame(
            'Existing Product SEO Description',
            $product->meta_description,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'title' => null,

                'description' => null,
            ],
        );
    }

    public function test_nullable_product_robots_values_preserve_inheritance(): void
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

                ogTitle: 'Social Title',

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $seo =
            $repository->get(
                $product,
            );

        $this->assertNull(
            $seo->robotsIndex,
        );

        $this->assertNull(
            $seo->robotsFollow,
        );
    }

    public function test_explicit_empty_extended_seo_removes_product_seo_row(): void
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

                canonicalUrl: 'https://example.com/products/custom',

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

        $this->assertDatabaseCount(
            'seo_meta',
            1,
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
}
