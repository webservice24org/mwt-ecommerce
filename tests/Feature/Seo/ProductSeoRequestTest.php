<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductSeoRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_update_without_seo_payload_preserves_existing_seo_meta(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Existing Product Title',

                    'meta_description' => 'Existing Product Description',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/products/original',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Existing OG Title',
            ]);

        $payload =
            $this->validProductPayload(
                $product,
            );

        /*
         * Deliberately omit the "seo" key.
         *
         * Existing Product forms do not submit this block yet.
         * The current seo_meta row must therefore be preserved.
         */
        unset(
            $payload[
                'seo'
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'canonical_url' => 'https://example.com/products/original',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Existing OG Title',
            ],
        );

        $product->refresh();

        /*
         * Existing Product-owned SEO fields remain authoritative.
         */
        $this->assertSame(
            'Existing Product Title',
            $product->meta_title,
        );

        $this->assertSame(
            'Existing Product Description',
            $product->meta_description,
        );
    }

    public function test_product_update_with_seo_payload_updates_extended_seo_meta(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Product SEO Title',

                    'meta_description' => 'Product SEO Description',
                ]);

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => 'https://example.com/products/custom-product',

            'robots_index' => false,

            'robots_follow' => true,

            'og_title' => 'Custom OG Product',

            'og_description' => 'Custom OG description.',

            'og_image' => 'https://example.com/images/product-og.jpg',

            'twitter_title' => 'Custom X Product',

            'twitter_description' => 'Custom X description.',

            'twitter_image' => 'https://example.com/images/product-x.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                /*
                 * Product title/description are intentionally
                 * NOT duplicated into seo_meta.
                 */
                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/products/custom-product',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Custom OG Product',

                'og_description' => 'Custom OG description.',

                'og_image' => 'https://example.com/images/product-og.jpg',

                'twitter_title' => 'Custom X Product',

                'twitter_description' => 'Custom X description.',

                'twitter_image' => 'https://example.com/images/product-x.jpg',
            ],
        );

        $product->refresh();

        $this->assertSame(
            'Product SEO Title',
            $product->meta_title,
        );

        $this->assertSame(
            'Product SEO Description',
            $product->meta_description,
        );
    }

    public function test_product_update_with_explicit_empty_seo_payload_removes_existing_overrides(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Product Title',

                    'meta_description' => 'Product Description',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/products/old',

                'robots_index' => false,

                'og_title' => 'Old OG Title',
            ]);

        $payload =
            $this->validProductPayload(
                $product,
            );

        /*
         * Explicitly submitted empty SEO means:
         * remove extended entity overrides.
         */
        $payload[
            'seo'
        ] = [
            'canonical_url' => null,

            'robots_index' => null,

            'robots_follow' => null,

            'og_title' => null,

            'og_description' => null,

            'og_image' => null,

            'twitter_title' => null,

            'twitter_description' => null,

            'twitter_image' => null,
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );

        /*
         * Clearing extended metadata must not delete the
         * Product's existing title/description fields.
         */
        $product->refresh();

        $this->assertSame(
            'Product Title',
            $product->meta_title,
        );

        $this->assertSame(
            'Product Description',
            $product->meta_description,
        );
    }

    public function test_product_seo_canonical_rejects_non_http_url(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => 'ftp://example.com/products/product',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.canonical_url',
            );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );
    }

    public function test_product_seo_open_graph_image_requires_valid_http_url(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'og_image' => 'product-og.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.og_image',
            );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );
    }

    public function test_product_seo_twitter_image_requires_valid_http_url(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'twitter_image' => 'javascript:alert(1)',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.twitter_image',
            );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );
    }

    public function test_product_seo_strings_are_trimmed_before_persistence(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => ' https://example.com/products/product ',

            'og_title' => '  Trimmed OG Title  ',

            'og_description' => '  Trimmed OG Description  ',

            'twitter_title' => '  Trimmed X Title  ',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'canonical_url' => 'https://example.com/products/product',

                'og_title' => 'Trimmed OG Title',

                'og_description' => 'Trimmed OG Description',

                'twitter_title' => 'Trimmed X Title',
            ],
        );
    }

    public function test_product_seo_blank_strings_are_normalized_to_null(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => '   ',

            'og_title' => '   ',

            'og_description' => '',

            'og_image' => '   ',

            'twitter_title' => '',

            'twitter_description' => '   ',

            'twitter_image' => '',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        /*
         * All SEO values normalized to null means the repository
         * has no meaningful overrides to persist.
         */
        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,
            ],
        );
    }

    public function test_false_robots_index_is_preserved_as_explicit_override(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'seo'
        ] = [
            'robots_index' => false,

            'robots_follow' => null,
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'robots_index' => false,

                'robots_follow' => null,
            ],
        );
    }

    public function test_product_meta_title_and_description_remain_owned_by_products_table(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $product =
            Product::factory()
                ->create();

        $payload =
            $this->validProductPayload(
                $product,
            );

        $payload[
            'meta_title'
        ] =
            'Updated Product Meta Title';

        $payload[
            'meta_description'
        ] =
            'Updated Product Meta Description';

        $payload[
            'seo'
        ] = [
            'og_title' => 'OG Override',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame(
            'Updated Product Meta Title',
            $product->meta_title,
        );

        $this->assertSame(
            'Updated Product Meta Description',
            $product->meta_description,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Product::class,

                'seoable_id' => $product->id,

                'title' => null,

                'description' => null,

                'og_title' => 'OG Override',
            ],
        );
    }

    /**
     * This mirrors the valid update contract already used by
     * ProductAdminCrudTest.
     *
     * @return array<string, mixed>
     */
    private function validProductPayload(
        Product $product,
    ): array {
        return [
            'brand_id' => null,

            'type' => ProductType::Simple->value,

            'sku' => 'SEO-PRODUCT-'.$product->id,

            'price' => 25000,

            'compare_at_price' => 30000,

            'cost_price' => 18000,

            'name' => $product->name,

            'slug' => $product->slug,

            'short_description' => $product->short_description,

            'description' => $product->description,

            'status' => ProductStatus::Draft->value,

            'is_featured' => false,

            'position' => 0,

            'published_at' => null,

            'meta_title' => $product->meta_title,

            'meta_description' => $product->meta_description,

            'category_ids' => [],
        ];
    }
}
