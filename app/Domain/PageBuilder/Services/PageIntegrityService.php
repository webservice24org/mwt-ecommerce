<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Services;

use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use DomainException;

final class PageIntegrityService
{
    public function assertTypeIsAvailable(
        PageType $type,
        ?int $ignorePageId = null,
    ): void {
        if ($type !== PageType::Home) {
            return;
        }

        $exists = Page::query()
            ->where('type', PageType::Home->value)
            ->when(
                $ignorePageId !== null,
                static fn ($query) => $query->whereKeyNot(
                    $ignorePageId,
                ),
            )
            ->exists();

        if ($exists) {
            throw new DomainException(
                'Only one homepage may exist.',
            );
        }
    }
}
