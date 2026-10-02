<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\SectionWidth;

final readonly class SectionLayoutData
{
    public function __construct(
        public SectionWidth $width,
    ) {}

    public static function default(): self
    {
        return new self(
            width: SectionWidth::Container,
        );
    }

    /**
     * @param  array<string, mixed>|null  $layout
     */
    public static function fromArray(
        ?array $layout,
    ): self {
        if ($layout === null) {
            return self::default();
        }

        $width = $layout['width']
            ?? null;

        if (! is_string($width)) {
            return self::default();
        }

        return new self(
            width: SectionWidth::tryFrom(
                $width,
            )
                ?? SectionWidth::Container,
        );
    }

    /**
     * @return array{
     *     width: string
     * }
     */
    public function toArray(): array
    {
        return [
            'width' => $this->width->value,
        ];
    }
}
