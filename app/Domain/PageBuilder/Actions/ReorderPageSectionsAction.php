<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\ReorderPageSectionsData;
use App\Domain\PageBuilder\Services\PageSectionPositionService;
use App\Models\Page;
use App\Models\PageSection;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReorderPageSectionsAction
{
    public function execute(
        Page $page,
        ReorderPageSectionsData $data,
    ): void {
        DB::transaction(
            function () use (
                $page,
                $data,
            ): void {
                $lockedPage = Page::query()
                    ->whereKey($page->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $sections = PageSection::query()
                    ->where(
                        'page_id',
                        $lockedPage->id,
                    )
                    ->lockForUpdate()
                    ->get([
                        'id',
                        'page_id',
                        'position',
                    ]);

                $existingIds = $sections
                    ->pluck('id')
                    ->map(
                        static fn (mixed $id): int => (int) $id,
                    )
                    ->sort()
                    ->values()
                    ->all();

                $requestedIds = $data->sectionIds;

                if (
                    count($requestedIds)
                    !== count(
                        array_unique($requestedIds),
                    )
                ) {
                    throw new DomainException(
                        'The section order contains duplicate section IDs.',
                    );
                }

                $sortedRequestedIds = $requestedIds;
                sort($sortedRequestedIds);

                if ($sortedRequestedIds !== $existingIds) {
                    throw new DomainException(
                        'The section order must contain every section belonging to the page exactly once.',
                    );
                }

                foreach (
                    $requestedIds as $index => $sectionId
                ) {
                    PageSection::query()
                        ->whereKey($sectionId)
                        ->where(
                            'page_id',
                            $lockedPage->id,
                        )
                        ->update([
                            'position' => (
                                $index + 1
                            ) * PageSectionPositionService::STEP,
                        ]);
                }
            },
        );
    }
}
