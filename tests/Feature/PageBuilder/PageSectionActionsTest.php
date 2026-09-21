<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Actions\CreatePageSectionAction;
use App\Domain\PageBuilder\Actions\DeletePageSectionAction;
use App\Domain\PageBuilder\Actions\ReorderPageSectionsAction;
use App\Domain\PageBuilder\Actions\UpdatePageSectionAction;
use App\Domain\PageBuilder\Data\CreatePageSectionData;
use App\Domain\PageBuilder\Data\ReorderPageSectionsData;
use App\Domain\PageBuilder\Data\UpdatePageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Models\Page;
use App\Models\PageSection;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSectionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_can_be_created_with_validated_config(): void
    {
        $page = Page::factory()->create();

        $section = app(
            CreatePageSectionAction::class,
        )->execute(
            $page,
            new CreatePageSectionData(
                type: SectionType::FeaturedProducts,
                template: 'grid',
                config: [
                    'title' => ' Featured Products ',
                    'limit' => 8,
                ],
            ),
        );

        $this->assertSame(
            $page->id,
            $section->page_id,
        );

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

        $this->assertSame(
            10,
            $section->position,
        );
    }

    public function test_new_sections_are_appended_in_order(): void
    {
        $page = Page::factory()->create();

        $action = app(
            CreatePageSectionAction::class,
        );

        $first = $action->execute(
            $page,
            $this->sectionData(),
        );

        $second = $action->execute(
            $page,
            $this->sectionData(),
        );

        $this->assertSame(
            10,
            $first->position,
        );

        $this->assertSame(
            20,
            $second->position,
        );
    }

    public function test_invalid_config_cannot_be_persisted(): void
    {
        $page = Page::factory()->create();

        try {
            app(
                CreatePageSectionAction::class,
            )->execute(
                $page,
                new CreatePageSectionData(
                    type: SectionType::FeaturedProducts,
                    template: 'grid',
                    config: [
                        'title' => 'Featured Products',
                        'limit' => 999,
                    ],
                ),
            );

            $this->fail(
                'Expected invalid configuration.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'limit',
                $exception->errors(),
            );
        }

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }

    public function test_section_can_be_updated(): void
    {
        $section = PageSection::factory()->create();

        $updated = app(
            UpdatePageSectionAction::class,
        )->execute(
            $section,
            new UpdatePageSectionData(
                type: SectionType::FeaturedProducts,
                template: 'grid',
                config: [
                    'title' => ' Updated ',
                    'limit' => 12,
                ],
                isEnabled: false,
            ),
        );

        $this->assertSame(
            [
                'title' => 'Updated',
                'limit' => 12,
            ],
            $updated->config,
        );

        $this->assertFalse(
            $updated->is_enabled,
        );
    }

    public function test_section_can_be_deleted(): void
    {
        $section = PageSection::factory()->create();

        app(
            DeletePageSectionAction::class,
        )->execute($section);

        $this->assertDatabaseMissing(
            'page_sections',
            [
                'id' => $section->id,
            ],
        );
    }

    public function test_sections_can_be_reordered(): void
    {
        $page = Page::factory()->create();

        $first = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 10,
        ]);

        $second = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 20,
        ]);

        $third = PageSection::factory()->create([
            'page_id' => $page->id,
            'position' => 30,
        ]);

        app(
            ReorderPageSectionsAction::class,
        )->execute(
            $page,
            new ReorderPageSectionsData([
                $third->id,
                $first->id,
                $second->id,
            ]),
        );

        $this->assertSame(
            10,
            $third->refresh()->position,
        );

        $this->assertSame(
            20,
            $first->refresh()->position,
        );

        $this->assertSame(
            30,
            $second->refresh()->position,
        );
    }

    public function test_reorder_rejects_duplicate_ids(): void
    {
        $page = Page::factory()->create();

        $first = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $second = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $this->expectException(
            DomainException::class,
        );

        app(
            ReorderPageSectionsAction::class,
        )->execute(
            $page,
            new ReorderPageSectionsData([
                $first->id,
                $first->id,
            ]),
        );
    }

    public function test_reorder_rejects_section_from_another_page(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::factory()->create([
            'page_id' => $page->id,
        ]);

        $otherSection = PageSection::factory()->create();

        $this->expectException(
            DomainException::class,
        );

        app(
            ReorderPageSectionsAction::class,
        )->execute(
            $page,
            new ReorderPageSectionsData([
                $section->id,
                $otherSection->id,
            ]),
        );
    }

    private function sectionData(): CreatePageSectionData
    {
        return new CreatePageSectionData(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
        );
    }
}
