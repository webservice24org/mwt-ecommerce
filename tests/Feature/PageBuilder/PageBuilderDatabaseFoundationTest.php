<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class PageBuilderDatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_casts_domain_values_correctly(): void
    {
        $page = Page::factory()->create([
            'type' => PageType::Home,
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        $page->refresh();

        $this->assertSame(PageType::Home, $page->type);
        $this->assertSame(PageStatus::Published, $page->status);
        $this->assertInstanceOf(Carbon::class, $page->published_at);
    }

    public function test_page_section_casts_domain_values_correctly(): void
    {
        $section = PageSection::factory()->create([
            'type' => SectionType::FeaturedProducts,
            'config' => [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            'position' => 10,
            'is_enabled' => true,
        ]);

        $section->refresh();

        $this->assertSame(
            SectionType::FeaturedProducts,
            $section->type,
        );

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
            $section->config,
        );

        $this->assertSame(10, $section->position);
        $this->assertTrue($section->is_enabled);
    }

    public function test_page_owns_ordered_sections(): void
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

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            $page->sections()
                ->pluck('id')
                ->all(),
        );
    }

    public function test_enabled_sections_exclude_disabled_sections(): void
    {
        $page = Page::factory()->create();

        $enabled = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
            'is_enabled' => true,
        ]);

        PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 20,
            'is_enabled' => false,
        ]);

        $this->assertSame(
            [$enabled->id],
            $page->enabledSections()
                ->pluck('id')
                ->all(),
        );
    }

    public function test_deleting_page_cascades_its_sections(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $page->delete();

        $this->assertDatabaseMissing(
            'page_sections',
            [
                'id' => $section->id,
            ],
        );
    }

    public function test_slug_must_be_unique(): void
    {
        Page::factory()->create([
            'slug' => 'about-us',
        ]);

        $this->expectException(
            QueryException::class,
        );

        Page::factory()->create([
            'slug' => 'about-us',
        ]);
    }

    public function test_published_scope_respects_publication_time(): void
    {
        $now = Carbon::parse(
            '2026-09-21 12:00:00',
        );

        Carbon::setTestNow($now);

        try {
            $publicImmediately = Page::factory()
                ->published()
                ->create([
                    'published_at' => null,
                ]);

            $publicPast = Page::factory()
                ->published()
                ->create([
                    'published_at' => $now->copy()->subMinute(),
                ]);

            $publicBoundary = Page::factory()
                ->published()
                ->create([
                    'published_at' => $now,
                ]);

            Page::factory()
                ->published()
                ->create([
                    'published_at' => $now->copy()->addMinute(),
                ]);

            Page::factory()->create([
                'status' => PageStatus::Draft,
                'published_at' => null,
            ]);

            $ids = Page::query()
                ->published()
                ->pluck('id')
                ->all();

            $this->assertContains(
                $publicImmediately->id,
                $ids,
            );

            $this->assertContains(
                $publicPast->id,
                $ids,
            );

            $this->assertContains(
                $publicBoundary->id,
                $ids,
            );

            $this->assertCount(3, $ids);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_is_published_respects_status_and_publication_time(): void
    {
        $now = Carbon::parse(
            '2026-09-21 12:00:00',
        );

        Carbon::setTestNow($now);

        try {
            $published = Page::factory()->make([
                'status' => PageStatus::Published,
                'published_at' => $now,
            ]);

            $future = Page::factory()->make([
                'status' => PageStatus::Published,
                'published_at' => $now->copy()->addSecond(),
            ]);

            $draft = Page::factory()->make([
                'status' => PageStatus::Draft,
                'published_at' => null,
            ]);

            $this->assertTrue($published->isPublished());
            $this->assertFalse($future->isPublished());
            $this->assertFalse($draft->isPublished());
        } finally {
            Carbon::setTestNow();
        }
    }
}
