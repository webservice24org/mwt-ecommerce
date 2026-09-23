<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Actions\CreatePageAction;
use App\Domain\PageBuilder\Actions\DeletePageAction;
use App\Domain\PageBuilder\Actions\UpdatePageAction;
use App\Domain\PageBuilder\Data\CreatePageData;
use App\Domain\PageBuilder\Data\UpdatePageData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_can_be_created(): void
    {
        $page = app(CreatePageAction::class)
            ->execute(
                new CreatePageData(
                    type: PageType::Standard,
                    title: 'About Us',
                    slug: null,
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Classic,
                    content: '<p>About our company.</p>',
                    metaTitle: 'About Us',
                    metaDescription: 'About our company.',
                    publishedAt: null,
                ),
            );

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'type' => PageType::Standard->value,
                'title' => 'About Us',
                'slug' => 'about-us',
                'status' => PageStatus::Draft->value,
                'content_mode' => PageContentMode::Classic->value,
                'content' => '<p>About our company.</p>',
                'meta_title' => 'About Us',
                'meta_description' => 'About our company.',
            ],
        );
    }

    public function test_builder_page_can_be_created(): void
    {
        $page = app(CreatePageAction::class)
            ->execute(
                new CreatePageData(
                    type: PageType::Standard,
                    title: 'Landing Page',
                    slug: 'landing-page',
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Builder,
                    content: null,
                    metaTitle: null,
                    metaDescription: null,
                    publishedAt: null,
                ),
            );

        $this->assertSame(
            PageContentMode::Builder,
            $page->content_mode,
        );

        $this->assertNull(
            $page->content,
        );

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'content_mode' => PageContentMode::Builder->value,
                'content' => null,
            ],
        );
    }

    public function test_create_generates_unique_slug(): void
    {
        Page::factory()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
        ]);

        $page = app(CreatePageAction::class)
            ->execute(
                new CreatePageData(
                    type: PageType::Standard,
                    title: 'About Us',
                    slug: null,
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Classic,
                    content: null,
                    metaTitle: null,
                    metaDescription: null,
                    publishedAt: null,
                ),
            );

        $this->assertNotSame(
            'about-us',
            $page->slug,
        );

        $this->assertDatabaseHas(
            'pages',
            [
                'id' => $page->id,
                'slug' => $page->slug,
            ],
        );
    }

    public function test_page_can_be_updated(): void
    {
        $page = Page::factory()->create([
            'title' => 'Old Title',
            'slug' => 'old-title',
        ]);

        $updated = app(UpdatePageAction::class)
            ->execute(
                $page,
                new UpdatePageData(
                    type: PageType::Standard,
                    title: 'New Title',
                    slug: null,
                    status: PageStatus::Published,
                    contentMode: PageContentMode::Classic,
                    content: '<p>Updated page content.</p>',
                    metaTitle: 'New Meta Title',
                    metaDescription: 'New description.',
                    publishedAt: now(),
                ),
            );

        $this->assertSame(
            'New Title',
            $updated->title,
        );

        $this->assertSame(
            'new-title',
            $updated->slug,
        );

        $this->assertSame(
            PageStatus::Published,
            $updated->status,
        );

        $this->assertSame(
            PageContentMode::Classic,
            $updated->content_mode,
        );

        $this->assertSame(
            '<p>Updated page content.</p>',
            $updated->content,
        );

        $this->assertSame(
            'New Meta Title',
            $updated->meta_title,
        );
    }

    public function test_update_can_keep_same_explicit_slug(): void
    {
        $page = Page::factory()->create([
            'slug' => 'about-us',
        ]);

        $updated = app(UpdatePageAction::class)
            ->execute(
                $page,
                new UpdatePageData(
                    type: PageType::Standard,
                    title: 'About Us Updated',
                    slug: 'about-us',
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Classic,
                    content: null,
                    metaTitle: null,
                    metaDescription: null,
                    publishedAt: null,
                ),
            );

        $this->assertSame(
            'about-us',
            $updated->slug,
        );
    }

    public function test_only_one_homepage_can_be_created(): void
    {
        Page::factory()->home()->create();

        $this->expectException(
            DomainException::class,
        );

        app(CreatePageAction::class)
            ->execute(
                new CreatePageData(
                    type: PageType::Home,
                    title: 'Another Home',
                    slug: 'another-home',
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Builder,
                    content: null,
                    metaTitle: null,
                    metaDescription: null,
                    publishedAt: null,
                ),
            );
    }

    public function test_standard_page_cannot_be_changed_to_home_when_home_exists(): void
    {
        Page::factory()
            ->home()
            ->create([
                'content_mode' => PageContentMode::Builder,
            ]);

        $page = Page::factory()->create([
            'type' => PageType::Standard,
            'content_mode' => PageContentMode::Classic,
            'content' => null,
        ]);

        $this->expectException(
            DomainException::class,
        );

        app(UpdatePageAction::class)
            ->execute(
                $page,
                new UpdatePageData(
                    type: PageType::Home,
                    title: $page->title,
                    slug: $page->slug,
                    status: $page->status,
                    contentMode: PageContentMode::Builder,
                    content: $page->content,
                    metaTitle: $page->meta_title,
                    metaDescription: $page->meta_description,
                    publishedAt: $page->published_at,
                ),
            );
    }

    public function test_existing_homepage_can_update_without_conflicting_with_itself(): void
    {
        $page = Page::factory()
            ->home()
            ->create();

        $updated = app(UpdatePageAction::class)
            ->execute(
                $page,
                new UpdatePageData(
                    type: PageType::Home,
                    title: 'Store Home',
                    slug: 'home',
                    status: PageStatus::Draft,
                    contentMode: PageContentMode::Builder,
                    content: null,
                    metaTitle: null,
                    metaDescription: null,
                    publishedAt: null,
                ),
            );

        $this->assertSame(
            PageType::Home,
            $updated->type,
        );

        $this->assertSame(
            PageContentMode::Builder,
            $updated->content_mode,
        );

        $this->assertSame(
            'Store Home',
            $updated->title,
        );
    }

    public function test_deleting_page_also_deletes_its_sections(): void
    {
        $page = Page::factory()->create();

        $section = $page
            ->sections()
            ->create([
                'type' => 'featured_products',
                'template' => 'grid',
                'config' => [
                    'title' => 'Featured Products',
                    'limit' => 8,
                ],
                'position' => 10,
                'is_enabled' => true,
            ]);

        app(DeletePageAction::class)
            ->execute($page);

        $this->assertDatabaseMissing(
            'pages',
            [
                'id' => $page->id,
            ],
        );

        $this->assertDatabaseMissing(
            'page_sections',
            [
                'id' => $section->id,
            ],
        );
    }
}
