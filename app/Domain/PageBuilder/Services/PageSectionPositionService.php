<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Services;

use App\Models\Page;
use App\Models\PageSection;

final class PageSectionPositionService
{
    public const STEP = 10;

    public function nextPosition(
        Page $page,
    ): int {
        $maximum = PageSection::query()
            ->where('page_id', $page->id)
            ->max('position');

        if ($maximum === null) {
            return self::STEP;
        }

        return ((int) $maximum) + self::STEP;
    }
}
