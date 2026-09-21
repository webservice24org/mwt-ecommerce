<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Domain\PageBuilder\Data\UpdatePageSectionData;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePageSectionAction
{
    public function __construct(
        private SectionConfigurationValidator $validator,
    ) {}

    public function execute(
        PageSection $section,
        UpdatePageSectionData $data,
    ): PageSection {
        return DB::transaction(
            function () use (
                $section,
                $data,
            ): PageSection {
                Page::query()
                    ->whereKey($section->page_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSection = PageSection::query()
                    ->whereKey($section->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $config = $this->validator->validate(
                    type: $data->type,
                    template: $data->template,
                    config: $data->config,
                );

                $lockedSection->update([
                    'type' => $data->type,
                    'template' => $data->template,
                    'config' => $config,
                    'is_enabled' => $data->isEnabled,
                ]);

                return $lockedSection->refresh();
            },
        );
    }
}
