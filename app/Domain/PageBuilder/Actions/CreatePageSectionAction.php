<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\CreatePageSectionData;
use App\Domain\PageBuilder\Services\PageSectionPositionService;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

final readonly class CreatePageSectionAction
{
    public function __construct(
        private SectionConfigurationValidator $validator,
        private PageSectionPositionService $positions,
    ) {}

    public function execute(
        Page $page,
        CreatePageSectionData $data,
    ): PageSection {
        return DB::transaction(
            function () use (
                $page,
                $data,
            ): PageSection {
                $lockedPage = Page::query()
                    ->whereKey($page->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $config = $this->validator->validate(
                    type: $data->type,
                    template: $data->template,
                    config: $data->config,
                );

                $position = $this->positions
                    ->nextPosition($lockedPage);

                return PageSection::query()->create([
                    'page_id' => $lockedPage->id,
                    'type' => $data->type,
                    'template' => $data->template,
                    'config' => $config,
                    'position' => $position,
                    'is_enabled' => $data->isEnabled,
                ]);
            },
        );
    }
}
