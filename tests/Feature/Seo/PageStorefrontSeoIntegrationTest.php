<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class PageStorefrontSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_receives_authoritatively_resolved_seo(): void
    {
        $page =
            Page::factory()
                ->create([
                    'title' => 'About MicroWeb',

                    'slug' => 'about-microweb',

                    'status' => PageStatus::Published,

                    'published_at' => now(),

                    'meta_title' => 'About MicroWeb SEO',

                    'meta_description' => 'Learn about MicroWeb Technology.',
                ]);

        $page
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/about-us',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'About MicroWeb OG',

                'og_description' => 'About MicroWeb social description.',

                'og_image' => 'https://example.com/pages/about-og.jpg',
            ]);

        $this
            ->get(
                route(
                    'frontend.pages.show',
                    [
                        'slug' => $page->slug,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $inertia,
                ) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->where(
                        'seo.description',
                        'Learn about MicroWeb Technology.',
                    )
                    ->where(
                        'seo.canonical_override',
                        'https://example.com/about-us',
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
                        'About MicroWeb OG',
                    )
                    ->where(
                        'seo.og_description',
                        'About MicroWeb social description.',
                    )
                    ->where(
                        'seo.og_image',
                        'https://example.com/pages/about-og.jpg',
                    )
                    ->where(
                        'seo.twitter_title',
                        'About MicroWeb OG',
                    )
                    ->where(
                        'seo.twitter_description',
                        'About MicroWeb social description.',
                    )
                    ->where(
                        'seo.twitter_image',
                        'https://example.com/pages/about-og.jpg',
                    )
                    ->has(
                        'page',
                    ),
            );
    }
}
