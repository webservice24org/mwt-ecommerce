<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Domain\PageBuilder\Data\ResolvedPageSectionData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Models\Page;
use Illuminate\Support\Collection;

final readonly class GetStorefrontHomepageQuery
{
    public function __construct(
        private PageSectionResolver $sectionResolver,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     sections: list<array<string, mixed>>
     * }|null
     */
    public function get(): ?array
    {
        $page = Page::query()
            ->published()
            ->where(
                'type',
                PageType::Home->value,
            )
            ->where(
                'content_mode',
                PageContentMode::Builder->value,
            )
            ->first();

        if ($page === null) {
            return null;
        }

        $sections = $this
            ->resolveSections($page);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'sections' => $sections
                ->map(
                    static fn (
                        ResolvedPageSectionData $section,
                    ): array => $section->toArray(),
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, ResolvedPageSectionData>
     */
    private function resolveSections(
        Page $page,
    ): Collection {
        return $page
            ->enabledSections()
            ->get()
            ->map(
                fn ($section): ResolvedPageSectionData => $this
                    ->sectionResolver
                    ->resolve(
                        $section,
                    ),
            );
    }
}
