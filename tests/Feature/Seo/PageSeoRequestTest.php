<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageLayout;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSeoRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_update_without_seo_meta_payload_preserves_existing_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create([
                    'meta_title' => 'Existing Page Meta Title',

                    'meta_description' => 'Existing Page Meta Description',
                ]);

        $page
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/pages/original',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Existing OG Title',
            ]);

        $payload =
            $this->validPayload(
                $page,
            );

        /*
         * Intentionally no "seo_meta" key.
         *
         * Existing Page forms do not submit extended SEO yet,
         * so existing overrides must remain untouched.
         */
        unset(
            $payload[
                'seo_meta'
            ],
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'canonical_url' => 'https://example.com/pages/original',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Existing OG Title',
            ],
        );

        $page->refresh();

        $this->assertSame(
            'Existing Page Meta Title',
            $page->meta_title,
        );

        $this->assertSame(
            'Existing Page Meta Description',
            $page->meta_description,
        );
    }

    public function test_page_update_with_seo_meta_payload_persists_extended_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create([
                    'meta_title' => 'Page Meta Title',

                    'meta_description' => 'Page Meta Description',
                ]);

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
        ] = [
            'canonical_url' => 'https://example.com/pages/custom-page',

            'robots_index' => false,

            'robots_follow' => true,

            'og_title' => 'Custom Page OG',

            'og_description' => 'Custom page OG description.',

            'og_image' => 'https://example.com/images/page-og.jpg',

            'twitter_title' => 'Custom Page X',

            'twitter_description' => 'Custom page X description.',

            'twitter_image' => 'https://example.com/images/page-x.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                /*
                 * Page title and description remain
                 * authoritative on the pages table.
                 */
                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/pages/custom-page',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Custom Page OG',

                'og_description' => 'Custom page OG description.',

                'og_image' => 'https://example.com/images/page-og.jpg',

                'twitter_title' => 'Custom Page X',

                'twitter_description' => 'Custom page X description.',

                'twitter_image' => 'https://example.com/images/page-x.jpg',
            ],
        );

        $page->refresh();

        $this->assertSame(
            'Page Meta Title',
            $page->meta_title,
        );

        $this->assertSame(
            'Page Meta Description',
            $page->meta_description,
        );
    }

    public function test_page_update_with_explicit_empty_seo_meta_payload_removes_existing_overrides(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create([
                    'meta_title' => 'Page Meta Title',

                    'meta_description' => 'Page Meta Description',
                ]);

        $page
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/pages/old',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Old OG Title',
            ]);

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
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
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,
            ],
        );

        /*
         * Clearing extended SEO must not clear the existing
         * Page-owned meta title and meta description.
         */
        $page->refresh();

        $this->assertSame(
            'Page Meta Title',
            $page->meta_title,
        );

        $this->assertSame(
            'Page Meta Description',
            $page->meta_description,
        );
    }

    public function test_page_canonical_url_rejects_non_http_protocol(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
        ] = [
            'canonical_url' => 'ftp://example.com/pages/test',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo_meta.canonical_url',
            );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,
            ],
        );
    }

    public function test_page_social_images_require_valid_http_urls(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
        ] = [
            'og_image' => 'page-og.jpg',

            'twitter_image' => 'javascript:alert(1)',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasErrors([
                'seo_meta.og_image',
                'seo_meta.twitter_image',
            ]);

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,
            ],
        );
    }

    public function test_page_seo_meta_strings_are_trimmed_before_persistence(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
        ] = [
            'canonical_url' => ' https://example.com/pages/trimmed ',

            'og_title' => '  Trimmed OG Title  ',

            'og_description' => '  Trimmed OG Description  ',

            'twitter_title' => '  Trimmed X Title  ',

            'twitter_description' => '  Trimmed X Description  ',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'canonical_url' => 'https://example.com/pages/trimmed',

                'og_title' => 'Trimmed OG Title',

                'og_description' => 'Trimmed OG Description',

                'twitter_title' => 'Trimmed X Title',

                'twitter_description' => 'Trimmed X Description',
            ],
        );
    }

    public function test_page_blank_seo_meta_strings_are_normalized_to_null(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
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
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        /*
         * Everything becomes null, therefore EntitySeoData has no
         * overrides and no seo_meta row should remain.
         */
        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,
            ],
        );
    }

    public function test_false_page_robots_index_is_preserved_as_explicit_override(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'seo_meta'
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
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'robots_index' => false,

                'robots_follow' => null,
            ],
        );
    }

    public function test_page_meta_title_and_description_remain_owned_by_pages_table(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $page =
            Page::factory()
                ->create();

        $payload =
            $this->validPayload(
                $page,
            );

        $payload[
            'meta_title'
        ] =
            'Updated Page Meta Title';

        $payload[
            'meta_description'
        ] =
            'Updated Page Meta Description';

        $payload[
            'seo_meta'
        ] = [
            'og_title' => 'Page OG Override',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.update',
                    $page,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $page->refresh();

        $this->assertSame(
            'Updated Page Meta Title',
            $page->meta_title,
        );

        $this->assertSame(
            'Updated Page Meta Description',
            $page->meta_description,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'title' => null,

                'description' => null,

                'og_title' => 'Page OG Override',
            ],
        );
    }

    /**
     * Mirrors the current Page admin update contract.
     *
     * @return array<string, mixed>
     */
    private function validPayload(
        Page $page,
    ): array {
        return [
            'type' => PageType::Standard->value,

            'layout' => PageLayout::RightSidebar->value,

            'show_breadcrumbs' => false,

            'title' => $page->title,

            'slug' => $page->slug,

            'status' => PageStatus::Draft->value,

            'content_mode' => PageContentMode::Classic->value,

            'content' => '<p>Page SEO request regression content.</p>',

            'meta_title' => $page->meta_title,

            'meta_description' => $page->meta_description,

            'published_at' => null,
        ];
    }
}
