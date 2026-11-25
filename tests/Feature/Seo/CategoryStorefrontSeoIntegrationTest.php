<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class CategoryStorefrontSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_receives_authoritatively_resolved_seo(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Laptop Computers',

                    'slug' => 'laptop-computers',

                    'description' => 'Shop our laptop computer collection.',

                    'meta_title' => 'Laptop Category SEO',

                    'meta_description' => 'Browse laptop computers.',

                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/custom-laptops',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Custom Laptop Category OG',

                'og_description' => 'Custom laptop category OG description.',

                'og_image' => 'https://example.com/categories/laptops-og.jpg',
            ]);

        $this
            ->get(
                route(
                    'categories.show',
                    [
                        'slug' => $category->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Frontend/Categories/Show',
                    )
                    ->where(
                        'seo.description',
                        'Browse laptop computers.',
                    )
                    ->where(
                        'seo.canonical_override',
                        'https://example.com/custom-laptops',
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
                        'Custom Laptop Category OG',
                    )
                    ->where(
                        'seo.og_description',
                        'Custom laptop category OG description.',
                    )
                    ->where(
                        'seo.og_image',
                        'https://example.com/categories/laptops-og.jpg',
                    )
                    ->where(
                        'seo.twitter_title',
                        'Custom Laptop Category OG',
                    )
                    ->where(
                        'seo.twitter_description',
                        'Custom laptop category OG description.',
                    )
                    ->where(
                        'seo.twitter_image',
                        'https://example.com/categories/laptops-og.jpg',
                    )
                    ->has(
                        'category',
                    )
                    ->has(
                        'products',
                    )
                    ->has(
                        'filters',
                    )
                    ->has(
                        'filterOptions',
                    ),
            );
    }

    public function test_base_category_preserves_resolved_index_policy(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Indexable Category',

                    'slug' => 'indexable-category',

                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'robots_index' => true,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'categories.show',
                    [
                        'slug' => $category->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        true,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    ),
            );
    }

    public function test_filtered_category_is_forced_to_noindex(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Filtered Category',

                    'slug' => 'filtered-category',

                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'robots_index' => true,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'categories.show',
                    [
                        'slug' => $category->slug,

                        'brand' => 'test-brand',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        false,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    ),
            );
    }

    public function test_route_policy_never_turns_entity_noindex_into_index(): void
    {
        $category =
            Category::factory()
                ->create([
                    'name' => 'Noindex Category',

                    'slug' => 'noindex-category',

                    'is_active' => true,
                ]);

        $category
            ->seoMeta()
            ->create([
                'robots_index' => false,

                'robots_follow' => true,
            ]);

        $this
            ->get(
                route(
                    'categories.show',
                    [
                        'slug' => $category->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'seo.robots_index',
                        false,
                    )
                    ->where(
                        'seo.robots_follow',
                        true,
                    ),
            );
    }
}
