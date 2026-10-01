<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\PageLayout;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Enums\SectionWidth;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageLayoutPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_uses_full_width_database_default(): void
    {
        $page = Page::query()->create([
            'type' => PageType::Standard,
            'title' => 'Default Layout',
            'slug' => 'default-layout',
            'status' => PageStatus::Draft,
        ]);

        $page->refresh();

        $this->assertSame(
            PageLayout::FullWidth,
            $page->layout,
        );
    }

    public function test_page_layout_is_cast_to_enum(): void
    {
        $page = Page::query()->create([
            'type' => PageType::Standard,
            'layout' => PageLayout::LeftSidebar,
            'title' => 'Left Sidebar',
            'slug' => 'left-sidebar',
            'status' => PageStatus::Draft,
        ]);

        $page->refresh();

        $this->assertSame(
            PageLayout::LeftSidebar,
            $page->layout,
        );
    }

    public function test_page_can_persist_right_sidebar_layout(): void
    {
        $page = Page::query()->create([
            'type' => PageType::Standard,
            'layout' => PageLayout::RightSidebar,
            'title' => 'Right Sidebar',
            'slug' => 'right-sidebar',
            'status' => PageStatus::Draft,
        ]);

        $page->refresh();

        $this->assertSame(
            PageLayout::RightSidebar,
            $page->layout,
        );
    }

    public function test_section_layout_can_be_absent_for_existing_style_rows(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::query()->create([
            'page_id' => $page->id,
            'type' => SectionType::Hero,
            'template' => 'static',
            'config' => [],
            'position' => 10,
            'is_enabled' => true,
        ]);

        $section->refresh();

        $this->assertNull(
            $section->layout,
        );
    }

    public function test_section_layout_is_persisted_as_array(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::query()->create([
            'page_id' => $page->id,
            'type' => SectionType::Hero,
            'template' => 'static',
            'config' => [],
            'layout' => [
                'width' =>
                    SectionWidth::Full->value,
            ],
            'position' => 10,
            'is_enabled' => true,
        ]);

        $section->refresh();

        $this->assertSame(
            [
                'width' =>
                    SectionWidth::Full->value,
            ],
            $section->layout,
        );
    }

    public function test_container_section_width_can_be_persisted(): void
    {
        $page = Page::factory()->create();

        $section = PageSection::query()->create([
            'page_id' => $page->id,
            'type' => SectionType::Hero,
            'template' => 'static',
            'config' => [],
            'layout' => [
                'width' =>
                    SectionWidth::Container->value,
            ],
            'position' => 10,
            'is_enabled' => true,
        ]);

        $section->refresh();

        $this->assertSame(
            SectionWidth::Container->value,
            $section->layout['width'],
        );
    }

    public function test_page_uses_show_breadcrumbs_database_default(): void
    {
        $page = Page::query()->create([
            'type' => PageType::Standard,
            'title' => 'Breadcrumb Default',
            'slug' => 'breadcrumb-default',
            'status' => PageStatus::Draft,
        ]);

        $page->refresh();

        $this->assertTrue(
            $page->show_breadcrumbs,
        );
    }

    public function test_page_can_persist_hidden_breadcrumbs(): void
    {
        $page = Page::query()->create([
            'type' => PageType::Standard,
            'layout' => PageLayout::RightSidebar,
            'show_breadcrumbs' => false,
            'title' => 'Hidden Breadcrumbs',
            'slug' => 'hidden-breadcrumbs',
            'status' => PageStatus::Draft,
        ]);

        $page->refresh();

        $this->assertSame(
            PageLayout::RightSidebar,
            $page->layout,
        );

        $this->assertFalse(
            $page->show_breadcrumbs,
        );
    }
}
