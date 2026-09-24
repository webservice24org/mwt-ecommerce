<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Services\PageSectionPositionService;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

final readonly class DuplicatePageSectionAction
{
    public function __construct(
        private SectionConfigurationValidator $validator,
    ) {}

    public function execute(
        Page $page,
        PageSection $section,
    ): PageSection {
        return DB::transaction(
            function () use ($page, $section): PageSection {
                $lockedPage = Page::query()
                    ->whereKey($page->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSection = PageSection::query()
                    ->whereKey($section->id)
                    ->where('page_id', $lockedPage->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $followingSections = PageSection::query()
                    ->where('page_id', $lockedPage->id)
                    ->where(
                        'position',
                        '>',
                        $lockedSection->position,
                    )
                    ->orderByDesc('position')
                    ->lockForUpdate()
                    ->get([
                        'id',
                        'position',
                    ]);

                foreach ($followingSections as $followingSection) {
                    PageSection::query()
                        ->whereKey($followingSection->id)
                        ->update([
                            'position' => (
                                (int) $followingSection->position
                            ) + PageSectionPositionService::STEP,
                        ]);
                }

                $config = $this->validator->validate(
                    type: $lockedSection->type,
                    template: $lockedSection->template,
                    config: $lockedSection->config,
                );

                return PageSection::query()->create([
                    'page_id' => $lockedPage->id,
                    'type' => $lockedSection->type,
                    'template' => $lockedSection->template,
                    'config' => $config,
                    'position' => (
                        (int) $lockedSection->position
                    ) + PageSectionPositionService::STEP,
                    'is_enabled' => $lockedSection->is_enabled,
                ]);
            },
        );
    }
}
