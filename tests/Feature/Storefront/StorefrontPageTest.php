<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StorefrontPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_can_be_viewed_publicly(): void
    {
        $cmsPage = Page::factory()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'content' => '<p>About our company.</p>',
            'published_at' => now()->subDay(),
            'meta_title' => 'About Our Company',
            'meta_description' => 'Learn more about us.',
        ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $cmsPage->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                function (Assert $page) use ($cmsPage): void {
                    $page
                        ->component(
                            'Frontend/Pages/Show',
                        )
                        ->where(
                            'page.id',
                            $cmsPage->id,
                        )
                        ->where(
                            'page.title',
                            'About Us',
                        )
                        ->where(
                            'page.slug',
                            'about-us',
                        )
                        ->where(
                            'page.content_mode',
                            PageContentMode::Classic->value,
                        )
                        ->where(
                            'page.content',
                            '<p>About our company.</p>',
                        )
                        ->where(
                            'page.seo.meta_title',
                            'About Our Company',
                        )
                        ->where(
                            'page.seo.meta_description',
                            'Learn more about us.',
                        )
                        ->has(
                            'page.sections',
                            0,
                        );
                },
            );
    }

    public function test_draft_page_returns_not_found(): void
    {
        $page = Page::factory()->create([
            'slug' => 'draft-page',
            'status' => PageStatus::Draft,
            'content_mode' => PageContentMode::Classic,
        ]);

        $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        )->assertNotFound();
    }

    public function test_future_page_returns_not_found(): void
    {
        $page = Page::factory()->create([
            'slug' => 'future-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'published_at' => now()->addDay(),
        ]);

        $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        )->assertNotFound();
    }
}
