<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Models\PageSection;

final class PageSectionDataFactory
{
    public function fromModel(PageSection $section): PageSectionData
    {
        return new PageSectionData(
            id: $section->id,
            type: $section->type,
            template: $section->template,
            config: $section->config,
            position: $section->position,
            isEnabled: $section->is_enabled,
        );
    }
}
