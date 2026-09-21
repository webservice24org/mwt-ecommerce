<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Collection;

final class PageSectionQuery
{
    /**
     * @return Collection<int, PageSection>
     */
    public function forPage(
        Page $page,
    ): Collection {
        return PageSection::query()
            ->where(
                'page_id',
                $page->id,
            )
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function findForPageOrFail(
        Page $page,
        int $sectionId,
    ): PageSection {
        return PageSection::query()
            ->where(
                'page_id',
                $page->id,
            )
            ->whereKey($sectionId)
            ->firstOrFail();
    }
}
