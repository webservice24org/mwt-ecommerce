<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategorySeoRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_update_without_seo_payload_preserves_existing_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create([
                    'meta_title' => 'Category Title',

                    'meta_description' => 'Category Description',
                ]);

        $category
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/category/original',

                'robots_index' => false,

                'robots_follow' => true,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $this->validPayload(
                    $category,
                ),
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'canonical_url' => 'https://example.com/category/original',

                'robots_index' => false,

                'robots_follow' => true,
            ],
        );
    }

    public function test_category_update_with_seo_payload_persists_extended_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => 'https://example.com/category/custom',

            'robots_index' => false,

            'robots_follow' => true,

            'og_title' => 'Category OG',

            'og_description' => 'Category OG description.',

            'og_image' => 'https://example.com/category-og.jpg',

            'twitter_title' => 'Category X',

            'twitter_description' => 'Category X description.',

            'twitter_image' => 'https://example.com/category-x.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/category/custom',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Category OG',

                'og_description' => 'Category OG description.',

                'og_image' => 'https://example.com/category-og.jpg',

                'twitter_title' => 'Category X',

                'twitter_description' => 'Category X description.',

                'twitter_image' => 'https://example.com/category-x.jpg',
            ],
        );
    }

    public function test_explicit_empty_category_seo_removes_existing_override(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $category
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/category/old',

                'robots_index' => false,
            ]);

        $payload =
            $this->validPayload(
                $category,
            );

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
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,
            ],
        );
    }

    public function test_category_canonical_url_rejects_non_http_protocol(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => 'ftp://example.com/category/test',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.canonical_url',
            );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,
            ],
        );
    }

    public function test_category_open_graph_image_requires_valid_http_url(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'seo'
        ] = [
            'og_image' => 'category-image.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.og_image',
            );
    }

    public function test_category_twitter_image_requires_valid_http_url(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
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
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.twitter_image',
            );
    }

    public function test_category_seo_strings_are_trimmed_before_persistence(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => ' https://example.com/category/test ',

            'og_title' => '  Category OG  ',

            'og_description' => '  Category OG description  ',

            'twitter_title' => '  Category X  ',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'canonical_url' => 'https://example.com/category/test',

                'og_title' => 'Category OG',

                'og_description' => 'Category OG description',

                'twitter_title' => 'Category X',
            ],
        );
    }

    public function test_category_blank_seo_strings_are_normalized_to_null(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'seo'
        ] = [
            'canonical_url' => '   ',

            'robots_index' => null,

            'robots_follow' => null,

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
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,
            ],
        );
    }

    public function test_false_category_robots_index_is_preserved_as_explicit_override(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
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
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'robots_index' => false,

                'robots_follow' => null,
            ],
        );
    }

    public function test_category_meta_title_and_description_remain_owned_by_categories_table(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $category =
            Category::factory()
                ->create();

        $payload =
            $this->validPayload(
                $category,
            );

        $payload[
            'meta_title'
        ] =
            'Updated Category Meta Title';

        $payload[
            'meta_description'
        ] =
            'Updated Category Meta Description';

        $payload[
            'seo'
        ] = [
            'og_title' => 'Category OG Override',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertSame(
            'Updated Category Meta Title',
            $category->meta_title,
        );

        $this->assertSame(
            'Updated Category Meta Description',
            $category->meta_description,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'title' => null,

                'description' => null,

                'og_title' => 'Category OG Override',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(
        Category $category,
    ): array {
        return [
            'parent_id' => $category->parent_id,

            'name' => $category->name,

            'slug' => $category->slug,

            'description' => $category->description,

            'position' => $category->position,

            'is_active' => $category->is_active,

            'meta_title' => $category->meta_title,

            'meta_description' => $category->meta_description,

            'remove_image' => false,
        ];
    }
}
