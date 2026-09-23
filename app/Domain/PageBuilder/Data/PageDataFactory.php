<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Models\Page;
use App\Models\PageSection;
use LogicException;

final class PageDataFactory
{
    public function __construct(
        private readonly PageSectionDataFactory $sectionFactory,
    ) {}

    public function fromModel(Page $page): PageData
    {
        if (! $page->relationLoaded('sections')) {
            throw new LogicException(
                'The [sections] relationship must be loaded before creating PageData.',
            );
        }

        return new PageData(
            id: $page->id,
            type: $page->type,
            title: $page->title,
            slug: $page->slug,
            status: $page->status,
            contentMode: $page->content_mode,
            content: $page->content,
            featuredImage: $page->featured_image,
            publishedAt: $page->published_at,
            seo: new PageSeoData(
                metaTitle: $page->meta_title,
                metaDescription: $page->meta_description,
            ),
            sections: $page->sections
                ->map(
                    fn (PageSection $section): PageSectionData => $this
                        ->sectionFactory
                        ->fromModel($section),
                )
                ->values()
                ->all(),
        );
    }
}
