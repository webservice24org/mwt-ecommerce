<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageType;

final readonly class StorefrontPageData
{
    /**
     * @param  list<ResolvedPageSectionData>  $sections
     */
    public function __construct(
        public int $id,
        public PageType $type,
        public string $title,
        public string $slug,
        public PageContentMode $contentMode,
        public ?string $content,
        public ?string $featuredImage,
        public PageSeoData $seo,
        public array $sections = [],
    ) {}

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     title: string,
     *     slug: string,
     *     content_mode: string,
     *     content: string|null,
     *     featured_image: string|null,
     *     seo: array{
     *         meta_title: string|null,
     *         meta_description: string|null
     *     },
     *     sections: list<array{
     *         id: int,
     *         type: string,
     *         template: string,
     *         config: array<string, mixed>,
     *         data: array<string, mixed>
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
            'content_mode' => $this->contentMode->value,
            'content' => $this->content,
            'featured_image' => $this->featuredImage,
            'seo' => $this->seo->toArray(),
            'sections' => array_map(
                static fn (
                    ResolvedPageSectionData $section,
                ): array => $section->toArray(),
                $this->sections,
            ),
        ];
    }
}
