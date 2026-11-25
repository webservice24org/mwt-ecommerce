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

    /**
     * Existing storefront DTO lookup.
     *
     * Kept for compatibility with any existing callers
     * that only need StorefrontPageData.
     */
    public function findBySlugOrFail(
        string $slug,
    ): StorefrontPageData {
        return $this->data(
            $this->findModelBySlugOrFail(
                $slug,
            ),
        );
    }

    /**
     * Authoritative storefront Page model lookup.
     *
     * Used when the caller needs both:
     *
     * - storefront page presentation data
     * - authoritative entity SEO resolution
     */
    public function findModelBySlugOrFail(
        string $slug,
    ): Page {
        return Page::query()
            ->published()
            ->with([
                'seoMeta',
            ])
            ->where(
                'slug',
                $slug,
            )
            ->firstOrFail();
    }

    /**
     * Build storefront presentation data from an already
     * resolved authoritative Page model.
     *
     * This prevents a second Page query when SEO and
     * Page Builder rendering are needed together.
     */
    public function data(
        Page $page,
    ): StorefrontPageData {
        $resolvedSections =
            match (
                $page->content_mode
            ) {
                PageContentMode::Builder => $this
                    ->sections
                    ->handle(
                        $page,
                    ),

                PageContentMode::Classic => [],
            };

        return new StorefrontPageData(
            id: $page->id,

            type: $page->type,

            layout: $page->layout,

            showBreadcrumbs: $page->show_breadcrumbs,

            title: $page->title,

            slug: $page->slug,

            contentMode: $page->content_mode,

            content: $page->content,

            featuredImage: $page->featured_image,

            seo: new PageSeoData(
                metaTitle: $page->meta_title,

                metaDescription: $page->meta_description,
            ),

            sections: $resolvedSections,
        );
    }
}
