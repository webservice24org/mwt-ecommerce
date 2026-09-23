<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use Carbon\CarbonInterface;

final readonly class PageData
{
    /**
     * @param  list<PageSectionData>  $sections
     */
    public function __construct(
        public ?int $id,
        public PageType $type,
        public string $title,
        public string $slug,
        public PageStatus $status,
        public PageContentMode $contentMode,
        public ?string $content,
        public ?string $featuredImage,
        public ?CarbonInterface $publishedAt,
        public PageSeoData $seo,
        public array $sections = [],
    ) {}

    /**
     * @return array{
     *     id: int|null,
     *     type: string,
     *     title: string,
     *     slug: string,
     *     status: string,
     *     content_mode: string,
     *     content: string|null,
     *     featured_image: string|null,
     *     published_at: string|null,
     *     seo: array{
     *         meta_title: string|null,
     *         meta_description: string|null
     *     },
     *     sections: list<array{
     *         id: int|null,
     *         type: string,
     *         template: string,
     *         config: array<string, mixed>,
     *         position: int,
     *         is_enabled: bool
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'content_mode' => $this->contentMode->value,
            'content' => $this->content,
            'featured_image' => $this->featuredImage,
            'published_at' => $this->publishedAt?->toIso8601String(),
            'seo' => $this->seo->toArray(),
            'sections' => array_map(
                static fn (PageSectionData $section): array => $section->toArray(),
                $this->sections,
            ),
        ];
    }
}
