<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Models\Page;

final readonly class PageIndexItemData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $type,
        public string $status,
        public int $sectionsCount,
        public ?string $publishedAt,
    ) {}

    public static function fromModel(
        Page $page,
    ): self {
        return new self(
            id: $page->id,
            title: $page->title,
            slug: $page->slug,
            type: $page->type->value,
            status: $page->status->value,
            sectionsCount: (int) $page->sections_count,
            publishedAt: $page->published_at?->toISOString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'status' => $this->status,
            'sections_count' => $this->sectionsCount,
            'published_at' => $this->publishedAt,
        ];
    }
}
