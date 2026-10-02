<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionLayoutData;
use App\Domain\PageBuilder\Enums\SectionWidth;
use PHPUnit\Framework\TestCase;

final class SectionLayoutDataTest extends TestCase
{
    public function test_default_layout_uses_container_width(): void
    {
        $layout =
            SectionLayoutData::default();

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );

        $this->assertSame(
            [
                'width' => 'container',
            ],
            $layout->toArray(),
        );
    }

    public function test_null_layout_uses_default_layout(): void
    {
        $layout =
            SectionLayoutData::fromArray(
                null,
            );

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );
    }

    public function test_empty_layout_uses_container_width(): void
    {
        $layout =
            SectionLayoutData::fromArray(
                [],
            );

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );
    }

    public function test_container_width_is_normalized(): void
    {
        $layout =
            SectionLayoutData::fromArray([
                'width' => 'container',
            ]);

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );
    }

    public function test_full_width_is_normalized(): void
    {
        $layout =
            SectionLayoutData::fromArray([
                'width' => 'full',
            ]);

        $this->assertSame(
            SectionWidth::Full,
            $layout->width,
        );
    }

    public function test_invalid_width_falls_back_to_container(): void
    {
        $layout =
            SectionLayoutData::fromArray([
                'width' => 'something_invalid',
            ]);

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );
    }

    public function test_non_string_width_falls_back_to_container(): void
    {
        $layout =
            SectionLayoutData::fromArray([
                'width' => 123,
            ]);

        $this->assertSame(
            SectionWidth::Container,
            $layout->width,
        );
    }
}
