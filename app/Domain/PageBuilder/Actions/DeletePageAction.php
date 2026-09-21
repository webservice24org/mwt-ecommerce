<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Actions;

use App\Models\Page;
use Illuminate\Support\Facades\DB;

final class DeletePageAction
{
    public function execute(
        Page $page,
    ): void {
        DB::transaction(
            static function () use ($page): void {
                $lockedPage = Page::query()
                    ->whereKey($page->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPage->delete();
            },
        );
    }
}
