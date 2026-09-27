<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\ResolvedPageSectionsQuery;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ResolvedPageSectionsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_only_enabled_sections(): void
    {
        $page = Page::factory()->create();

        $enabled = $this->createSection(
            page: $page,
            position: 1,
            enabled: true,
            title: 'Visible',
        );

        $this->createSection(
            page: $page,
            position: 2,
            enabled: false,
            title: 'Hidden',
        );

        $sections = app(
            ResolvedPageSectionsQuery::class,
        )->handle($page);

        $this->assertCount(
            1,
            $sections,
        );

        $this->assertSame(
            $enabled->id,
            $sections[0]->id,
        );
    }

    public function test_it_preserves_section_position_order(): void
    {
        $page = Page::factory()->create();

        $third = $this->createSection(
            page: $page,
            position: 30,
            enabled: true,
            title: 'Third',
        );

        $first = $this->createSection(
            page: $page,
            position: 10,
            enabled: true,
            title: 'First',
        );

        $second = $this->createSection(
            page: $page,
            position: 20,
            enabled: true,
            title: 'Second',
        );

        $sections = app(
            ResolvedPageSectionsQuery::class,
        )->handle($page);

        $this->assertSame(
            [
                $first->id,
                $second->id,
                $third->id,
            ],
            array_map(
                static fn ($section): int => $section->id,
                $sections,
            ),
        );
    }

    private function createSection(
        Page $page,
        int $position,
        bool $enabled,
        string $title,
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
