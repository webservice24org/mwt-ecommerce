<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\StorefrontPageQuery;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontPageQueryTest extends TestCase
{
    use RefreshDatabase;

    private StorefrontPageQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = app(
            StorefrontPageQuery::class,
        );
    }

    public function test_it_returns_published_classic_page_by_slug(): void
    {
        $page = Page::factory()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'content' => '<p>About our company.</p>',
            'published_at' => now()->subDay(),
            'meta_title' => 'About Our Company',
            'meta_description' => 'Learn more about our company.',
        ]);

        $result = $this->query->findBySlugOrFail(
            'about-us',
        );

        $this->assertSame(
            $page->id,
            $result->id,
        );

        $this->assertSame(
            'About Us',
            $result->title,
        );

        $this->assertSame(
            PageContentMode::Classic,
            $result->contentMode,
        );

        $this->assertSame(
            '<p>About our company.</p>',
            $result->content,
        );

        $this->assertSame(
            [],
            $result->sections,
        );

        $this->assertSame(
            'About Our Company',
            $result->seo->metaTitle,
        );

        $this->assertSame(
            'Learn more about our company.',
            $result->seo->metaDescription,
        );
    }

    public function test_draft_page_cannot_be_resolved_publicly(): void
    {
        Page::factory()->create([
            'slug' => 'draft-page',
            'status' => PageStatus::Draft,
            'content_mode' => PageContentMode::Classic,
        ]);

        $this->expectException(
            ModelNotFoundException::class,
        );

        $this->query->findBySlugOrFail(
            'draft-page',
        );
    }

    public function test_future_scheduled_page_cannot_be_resolved_publicly(): void
    {
        Page::factory()->create([
            'slug' => 'future-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'published_at' => now()->addDay(),
        ]);

        $this->expectException(
            ModelNotFoundException::class,
        );

        $this->query->findBySlugOrFail(
            'future-page',
        );
    }

    public function test_builder_page_resolves_only_enabled_sections_in_position_order(): void
    {
        $page = Page::factory()->create([
            'slug' => 'landing-page',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subDay(),
        ]);

        $third = $this->createSection(
            page: $page,
            title: 'Third',
            position: 30,
            enabled: true,
        );

        $first = $this->createSection(
            page: $page,
            title: 'First',
            position: 10,
            enabled: true,
        );

        $this->createSection(
            page: $page,
            title: 'Hidden',
            position: 15,
            enabled: false,
        );

        $second = $this->createSection(
            page: $page,
            title: 'Second',
            position: 20,
            enabled: true,
        );

        $result = $this->query->findBySlugOrFail(
            'landing-page',
        );

        $this->assertSame(
            PageContentMode::Builder,
            $result->contentMode,
        );

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            array_map(
                static fn (
                    ResolvedPageSectionData $section,
                ): int => $section->id,
                $result->sections,
            )
        );
    }

    public function test_public_serialization_does_not_expose_admin_publication_state(): void
    {
        Page::factory()->create([
            'slug' => 'privacy',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Classic,
            'published_at' => now()->subDay(),
        ]);

        $data = $this->query
            ->findBySlugOrFail('privacy')
            ->toArray();

        $this->assertArrayNotHasKey(
            'status',
            $data,
        );

        $this->assertArrayNotHasKey(
            'published_at',
            $data,
        );

        $this->assertArrayHasKey(
            'seo',
            $data,
        );

        $this->assertArrayHasKey(
            'sections',
            $data,
        );
    }

    private function createSection(
        Page $page,
        string $title,
        int $position,
        bool $enabled,
    ): PageSection {
        return PageSection::factory()->create([
            'page_id' => $page->id,
            'type' => SectionType::FeaturedProducts,
            'template' => 'grid',
            'config' => [
                'title' => $title,
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            'position' => $position,
            'is_enabled' => $enabled,
        ]);
    }
}
