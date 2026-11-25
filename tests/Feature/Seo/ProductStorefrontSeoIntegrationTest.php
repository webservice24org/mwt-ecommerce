<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class ProductStorefrontSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_receives_authoritatively_resolved_seo(): void
    {
        $product =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'SEO Laptop',

                    'slug' => 'seo-laptop',

                    'meta_title' => 'Custom Laptop Title',

                    'meta_description' => 'Custom laptop description.',
                ]);

        $product
            ->seoMeta()
            ->create([
                'og_title' => 'Custom OG Laptop',

                'og_description' => 'Custom OG description.',

                'og_image' => 'https://example.com/seo/laptop-og.jpg',

                'robots_index' => false,

                'robots_follow' => true,
                'canonical_url' => 'https://example.com/custom-product',
            ]);

        $this
            ->get(
                '/products/seo-laptop',
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Frontend/Products/Show',
                    )
                    ->where(
                        'seo.description',
                        'Custom laptop description.',
                    )
                    ->where(
                        'seo.robots_index',
                        false,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    )
                    ->where(
                        'seo.og_title',
                        'Custom OG Laptop',
                    )
                    ->where(
                        'seo.og_description',
                        'Custom OG description.',
                    )
                    ->where(
                        'seo.og_image',
                        'https://example.com/seo/laptop-og.jpg',
                    )
                    ->where(
                        'seo.twitter_title',
                        'Custom OG Laptop',
                    )
                    ->where(
                        'seo.twitter_image',
                        'https://example.com/seo/laptop-og.jpg',
                    )
                    ->where(
                        'seo.canonical_override',
                        'https://example.com/custom-product',
                    )
                    ->where(
                        'seo.canonical_url',
                        'https://example.com/custom-product',
                    )
                    ->where(
                        'seo.robots_index',
                        false,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    )
                    ->has(
                        'product',
                    )
                    ->has(
                        'reviewState',
                    )
                    ->has(
                        'reviews',
                    ),
            );
    }
}
