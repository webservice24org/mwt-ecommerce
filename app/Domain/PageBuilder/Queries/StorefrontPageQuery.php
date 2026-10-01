<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\PageSeoData;
use App\Domain\PageBuilder\Data\StorefrontPageData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Models\Page;

final readonly class StorefrontPageQuery
{
    public function __construct(
        private ResolvedPageSectionsQuery $sections,
    ) {}

    public function findBySlugOrFail(
        string $slug,
    ): StorefrontPageData {
        $page = Page::query()
            ->published()
            ->where(
                'slug',
                $slug,
            )
            ->firstOrFail();

        $resolvedSections = match (
            $page->content_mode
        ) {
            PageContentMode::Builder => $this
                ->sections
                ->handle($page),

            PageContentMode::Classic => [],
        };

        return new StorefrontPageData(
            id: $page->id,
            type: $page->type,
            layout: $page->layout,
            showBreadcrumbs:
                $page->show_breadcrumbs,
            title: $page->title,
            slug: $page->slug,
            contentMode: $page->content_mode,
            content: $page->content,
            featuredImage: $page->featured_image,
            seo: new PageSeoData(
                metaTitle: $page->meta_title,
                metaDescription:
                    $page->meta_description,
            ),
            sections: $resolvedSections,
        );
    }
}