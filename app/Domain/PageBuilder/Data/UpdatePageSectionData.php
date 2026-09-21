<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\SectionType;

final readonly class UpdatePageSectionData
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public SectionType $type,
        public string $template,
        public array $config,
        public bool $isEnabled,
    ) {}
}
