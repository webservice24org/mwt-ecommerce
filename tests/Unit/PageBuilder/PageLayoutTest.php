<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\PageLayout;
use PHPUnit\Framework\TestCase;

final class PageLayoutTest extends TestCase
{
    public function test_it_exposes_expected_values(): void
    {
        $this->assertSame(
            'full_width',
            PageLayout::FullWidth->value,
        );

        $this->assertSame(
            'left_sidebar',
            PageLayout::LeftSidebar->value,
        );

        $this->assertSame(
            'right_sidebar',
            PageLayout::RightSidebar->value,
        );
    }

    public function test_it_exposes_expected_labels(): void
    {
        $this->assertSame(
            'Full Width',
            PageLayout::FullWidth->label(),
        );

        $this->assertSame(
            'Left Sidebar',
            PageLayout::LeftSidebar->label(),
        );

        $this->assertSame(
            'Right Sidebar',
            PageLayout::RightSidebar->label(),
        );
    }

    public function test_it_contains_exactly_three_layouts(): void
    {
        $this->assertCount(
            3,
            PageLayout::cases(),
        );
    }

    public function test_it_can_be_created_from_persisted_values(): void
    {
        $this->assertSame(
            PageLayout::FullWidth,
            PageLayout::from(
                'full_width',
            ),
        );

        $this->assertSame(
            PageLayout::LeftSidebar,
            PageLayout::from(
                'left_sidebar',
            ),
        );

        $this->assertSame(
            PageLayout::RightSidebar,
            PageLayout::from(
                'right_sidebar',
            ),
        );
    }

    public function test_invalid_layout_cannot_be_created(): void
    {
        $this->assertNull(
            PageLayout::tryFrom(
                'invalid',
            ),
        );
    }
}
