<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Queries\PageEditQuery;
use App\Domain\PageBuilder\Queries\PageIndexQuery;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_query_can_filter_by_status(): void
    {
        $draft = Page::factory()->create([
            'status' => PageStatus::Draft,
        ]);

        Page::factory()->create([
            'status' => PageStatus::Published,
        ]);

        $pages = app(PageIndexQuery::class)
            ->paginate(
                status: PageStatus::Draft,
            );

        $this->assertSame(
            [$draft->id],
            $pages
                ->getCollection()
                ->pluck('id')
                ->all(),
        );
    }

    public function test_index_query_can_filter_by_type(): void
    {
        $home = Page::factory()
            ->home()
            ->create();

        Page::factory()->create([
            'type' => PageType::Standard,
        ]);

        $pages = app(PageIndexQuery::class)
            ->paginate(
                type: PageType::Home,
            );

        $this->assertSame(
            [$home->id],
            $pages
                ->getCollection()
                ->pluck('id')
                ->all(),
        );
    }

    public function test_index_query_can_search_title_and_slug(): void
    {
        $about = Page::factory()->create([
            'title' => 'About Our Company',
            'slug' => 'about-company',
        ]);

        Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
        ]);

        $pages = app(PageIndexQuery::class)
            ->paginate(
                search: 'about',
            );

        $this->assertSame(
            [$about->id],
            $pages
                ->getCollection()
                ->pluck('id')
                ->all(),
        );
    }

    public function test_index_query_includes_section_count_without_loading_sections(): void
    {
        $page = Page::factory()->create();

        PageSection::factory()
            ->count(3)
            ->create([
                'page_id' => $page->id,
            ]);

        $result = app(PageIndexQuery::class)
            ->paginate();

        $indexedPage = $result
            ->getCollection()
            ->firstWhere(
                'id',
                $page->id,
            );

        $this->assertNotNull(
            $indexedPage,
        );

        $this->assertSame(
            3,
            $indexedPage->sections_count,
        );

        $this->assertFalse(
            $indexedPage->relationLoaded(
                'sections',
            ),
        );
    }

    public function test_edit_query_eager_loads_sections_in_builder_order(): void
    {
        $page = Page::factory()->create();

        $third = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 30,
        ]);

        $first = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
        ]);

        $second = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 20,
        ]);

        $result = app(PageEditQuery::class)
            ->findOrFail($page->id);

        $this->assertTrue(
            $result->relationLoaded(
                'sections',
            ),
        );

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            $result->sections
                ->pluck('id')
                ->all(),
        );
    }
}
