<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionWidth;
use PHPUnit\Framework\TestCase;

final class SectionWidthTest extends TestCase
{
    public function test_it_exposes_expected_values(): void
    {
        $this->assertSame(
            'container',
            SectionWidth::Container->value,
        );

        $this->assertSame(
            'full',
            SectionWidth::Full->value,
        );
    }

    public function test_it_exposes_expected_labels(): void
    {
        $this->assertSame(
            'Container',
            SectionWidth::Container->label(),
        );

        $this->assertSame(
            'Full Width',
            SectionWidth::Full->label(),
        );
    }

    public function test_it_contains_exactly_two_widths(): void
    {
        $this->assertCount(
            2,
            SectionWidth::cases(),
        );
    }

    public function test_it_can_be_created_from_persisted_values(): void
    {
        $this->assertSame(
            SectionWidth::Container,
            SectionWidth::from(
                'container',
            ),
        );

        $this->assertSame(
            SectionWidth::Full,
            SectionWidth::from(
                'full',
            ),
        );
    }

    public function test_invalid_width_cannot_be_created(): void
    {
        $this->assertNull(
            SectionWidth::tryFrom(
                'invalid',
            ),
        );
    }
}
