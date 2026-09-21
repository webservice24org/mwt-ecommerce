<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Queries\PageSectionQuery;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSectionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_are_returned_in_builder_order(): void
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

        $sections = app(
            PageSectionQuery::class,
        )->forPage($page);

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            $sections->pluck('id')->all(),
        );
    }

    public function test_section_lookup_is_scoped_to_parent_page(): void
    {
        $page = Page::factory()->create();

        $otherSection = PageSection::factory()->create();

        $this->expectException(
            ModelNotFoundException::class,
        );

        app(
            PageSectionQuery::class,
        )->findForPageOrFail(
            $page,
            $otherSection->id,
        );
    }
}
