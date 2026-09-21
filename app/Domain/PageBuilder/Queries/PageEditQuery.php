<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Queries;

use App\Models\Page;

final class PageEditQuery
{
    public function findOrFail(
        int $id,
    ): Page {
        return Page::query()
            ->with([
                'sections',
            ])
            ->findOrFail($id);
    }
}
