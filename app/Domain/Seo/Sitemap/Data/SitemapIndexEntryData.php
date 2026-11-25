<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Data;

use App\Domain\Seo\Sitemap\Enums\SitemapType;
use DateTimeImmutable;

final readonly class SitemapIndexEntryData
{
    public function __construct(
        public SitemapType $type,
        public int $partNumber,
        public string $filename,
        public ?DateTimeImmutable $lastModified,
    ) {}

    public static function fromPart(
        SitemapPartData $part,
    ): self {
        return new self(
            type: $part->type,

            partNumber: $part->partNumber,

            filename: $part->filename(),

            lastModified: $part->lastModified(),
        );
    }

    /**
     * @return array{
     *     type: string,
     *     part: int,
     *     filename: string,
     *     last_modified: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,

            'part' => $this->partNumber,

            'filename' => $this->filename,

            'last_modified' => $this
                ->lastModified
                ?->format(
                    DATE_ATOM,
                ),
        ];
    }
}
