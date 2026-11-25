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

    public function fromModel(
        Page $page,
    ): PageData {
        if (
            ! $page->relationLoaded(
                'sections',
            )
        ) {
            throw new LogicException(
                'The [sections] relationship must be loaded before creating PageData.',
            );
        }

        if (
            ! $page->relationLoaded(
                'seoMeta',
            )
        ) {
            throw new LogicException(
                'The [seoMeta] relationship must be loaded before creating PageData.',
            );
        }

        return new PageData(
            id: $page->id,
            type: $page->type,
            title: $page->title,
            layout: $page->layout,
            showBreadcrumbs: $page->show_breadcrumbs,
            slug: $page->slug,
            status: $page->status,
            contentMode: $page->content_mode,
            content: $page->content,
            featuredImage: $page->featured_image,
            publishedAt: $page->published_at,
            seo: new PageSeoData(
                metaTitle: $page->meta_title,

                metaDescription: $page->meta_description,

                canonicalUrl: $page
                    ->seoMeta
                    ?->canonical_url,

                robotsIndex: $page
                    ->seoMeta
                    ?->robots_index,

                robotsFollow: $page
                    ->seoMeta
                    ?->robots_follow,

                ogTitle: $page
                    ->seoMeta
                    ?->og_title,

                ogDescription: $page
                    ->seoMeta
                    ?->og_description,

                ogImage: $page
                    ->seoMeta
                    ?->og_image,

                twitterTitle: $page
                    ->seoMeta
                    ?->twitter_title,

                twitterDescription: $page
                    ->seoMeta
                    ?->twitter_description,

                twitterImage: $page
                    ->seoMeta
                    ?->twitter_image,
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
