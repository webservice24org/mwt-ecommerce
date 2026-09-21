<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;

final class DeletePageSectionAction
{
    public function execute(
        PageSection $section,
    ): void {
        DB::transaction(
            static function () use ($section): void {
                Page::query()
                    ->whereKey($section->page_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSection = PageSection::query()
                    ->whereKey($section->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSection->delete();
            },
        );
    }
}
